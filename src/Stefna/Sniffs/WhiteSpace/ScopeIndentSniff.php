<?php declare(strict_types=1);

/**
 * Modified version of the original `Generic.Whitespace.ScopeIndent` https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/f4e17489b1f765520b61ec8a6242d90b2fb94cb8/src/Standards/Generic/Sniffs/WhiteSpace/ScopeIndentSniff.php
 */

namespace Stefna\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Util\Writers\StatusWriter;
use Stefna\Utils\TokenCollection;

class ScopeIndentSniff implements Sniff
{
	/**
	 * The number of spaces code should be indented.
	 */
	public int $indent = 4;

	/**
	 * Does the indent need to be exactly right?
	 *
	 * If TRUE, indent needs to be exactly $indent spaces. If FALSE,
	 * indent needs to be at least $indent spaces (but can be more).
	 */
	public bool $exact = false;

	/**
	 * Should tabs be used for indenting?
	 *
	 * If TRUE, fixes will be made using tabs instead of spaces.
	 * The size of each tab is important, so it should be specified
	 * using the --tab-width CLI argument.
	 */
	public bool $tabIndent = false;

	/**
	 * The --tab-width CLI value that is being used.
	 */
	private int|null $tabWidth = null;

	/**
	 * List of tokens not needing to be checked for indentation.
	 *
	 * Useful to allow Sniffs based on this to easily ignore/skip some
	 * tokens from verification. For example, inline HTML sections
	 * or PHP open/close tags can escape from here and have their own
	 * rules elsewhere.
	 *
	 * @var int[]
	 */
	public array $ignoreIndentationTokens = [];

	/**
	 * List of tokens not needing to be checked for indentation.
	 *
	 * This is a cached copy of the public version of this var, which
	 * can be set in a ruleset file, and some core ignored tokens.
	 *
	 * @var array<int|string, bool>
	 */
	private array $ignoreIndentation = [];

	/**
	 * Any scope openers that should not cause an indent.
	 *
	 * @var int[]
	 */
	protected array $nonIndentingScopes = [];

	/**
	 * Show debug output for this sniff.
	 */
	private bool $debug = false;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|string>
	 */
	public function register(): array
	{
		return [T_OPEN_TAG];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 */
	public function process(File $phpcsFile, int $stackPtr): int
	{
		$debug = Config::getConfigData('scope_indent_debug');
		if ($debug !== null) {
			$this->debug = (bool) $debug;
		}

		if ($this->tabWidth === null) {
			if (isset($phpcsFile->config->tabWidth) === false || $phpcsFile->config->tabWidth === 0) {
				// We have no idea how wide tabs are, so assume 4 spaces for fixing.
				// It shouldn't really matter because indent checks elsewhere in the
				// standard should fix things up.
				$this->tabWidth = 4;
			}
			else {
				$this->tabWidth = $phpcsFile->config->tabWidth;
			}
		}

		$lastOpenTag       = $stackPtr;
		$lastCloseTag      = null;
		$openScopes        = [];
		$adjustments       = [];
		$setIndents        = [];
		$disableExactStack = [];
		$disableExactEnd   = 0;

		$rawTokens  = $phpcsFile->getTokens();
		$tokens = new TokenCollection($phpcsFile->getTokens());
		$first   = $phpcsFile->findFirstOnLine(T_INLINE_HTML, $stackPtr);
		$trimmed = $first === false ? '' : ltrim($tokens->content($first));
		if ($trimmed === '') {
			$currentIndent = ($tokens->column($stackPtr) - 1);
		}
		else {
			$currentIndent = (strlen($tokens->content($first)) - strlen($trimmed));
		}

		if ($this->debug === true) {
			$line = $tokens->line($stackPtr);
			StatusWriter::writeNewline();
			StatusWriter::write("Start with token $stackPtr on line $line with indent $currentIndent");
		}

		if (empty($this->ignoreIndentation) === true) {
			$this->ignoreIndentation = [T_INLINE_HTML => true];
			foreach ($this->ignoreIndentationTokens as $token) {
				if (is_int($token) === false) {
					if (defined($token) === false) {
						continue;
					}

					$token = constant($token);
				}

				$this->ignoreIndentation[$token] = true;
			}
		}

		$this->exact     = (bool) $this->exact;
		$this->tabIndent = (bool) $this->tabIndent;

		$checkAnnotations = $phpcsFile->config->annotations;

		for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
			if ($i === false) {
				// Something has gone very wrong; maybe a parse error.
				break;
			}

			if (
				$checkAnnotations === true
				&& $tokens->code($i) === T_PHPCS_SET
				&& isset($rawTokens[$i]['sniffCode']) === true
				&& $rawTokens[$i]['sniffCode'] === 'Generic.WhiteSpace.ScopeIndent'
				&& $rawTokens[$i]['sniffProperty'] === 'exact'
			) {
				$value = $rawTokens[$i]['sniffPropertyValue'];
				if ($value === 'true') {
					$value = true;
				}
				elseif ($value === 'false') {
					$value = false;
				}
				else {
					$value = (bool) $value;
				}

				$this->exact = $value;

				if ($this->debug === true) {
					$line = $tokens->line($i);
					if ($this->exact === true) {
						$value = 'true';
					}
					else {
						$value = 'false';
					}

					StatusWriter::write("* token $i on line $line set exact flag to $value *");
				}
			}

			$checkToken  = null;
			$checkIndent = null;

			/*
				Don't check indents exactly between parenthesis or arrays as they
				tend to have custom rules, such as with multi-line function calls
				and control structure conditions.
			*/

			$exact = $this->exact;

			if (
				$tokens->code($i) === T_OPEN_PARENTHESIS
				&& $tokens->hasParenthesisCloser($i)
			) {
				$disableExactStack[$tokens->parenthesisCloser($i)] = $tokens->parenthesisCloser($i);
				$disableExactEnd = max($disableExactEnd, $tokens->parenthesisCloser($i));
				if ($this->debug === true) {
					$line = $tokens->line($i);
					$type = $rawTokens[$disableExactEnd]['type'];
					StatusWriter::write("Opening parenthesis found on line $line");
					StatusWriter::write("=> disabling exact indent checking until $disableExactEnd ($type)", 1);
				}
			}

			if ($exact === true && $i < $disableExactEnd) {
				$exact = false;
			}

			// Detect line changes and figure out where the indent is.
			if ($tokens->column($i) === 1) {
				$trimmed = ltrim($tokens->content($i));
				if ($trimmed === '') {
					if (
						$tokens->has($i + 1)
						&& $tokens->sameLine($i, $i + 1)
					) {
						$checkToken  = ($i + 1);
						$tokenIndent = ($tokens->column($i + 1) - 1);
					}
				}
				else {
					$checkToken  = $i;
					$tokenIndent = (strlen($tokens->content($i)) - strlen($trimmed));
				}
			}

			// Closing parenthesis should just be indented to at least
			// the same level as where they were opened (but can be more).
			if (
				($checkToken !== null
				&& $tokens->code($checkToken) === T_CLOSE_PARENTHESIS
				&& $tokens->hasParenthesisOpener($checkToken))
				|| ($tokens->code($i) === T_CLOSE_PARENTHESIS
				&& $tokens->hasParenthesisOpener($i))
			) {
				if ($checkToken !== null) {
					$parenCloser = $checkToken;
				}
				else {
					$parenCloser = $i;
				}

				if ($this->debug === true) {
					$line = $tokens->line($i);
					StatusWriter::write("Closing parenthesis found on line $line");
				}

				$parenOpener = $tokens->parenthesisOpener($parenCloser);
				if (!$tokens->sameLine($parenCloser, $parenOpener)) {
					$parens = 0;
					if (
						isset($rawTokens[$parenCloser]['nested_parenthesis']) === true
						&& empty($rawTokens[$parenCloser]['nested_parenthesis']) === false
					) {
						$parens = $rawTokens[$parenCloser]['nested_parenthesis'];
						end($parens);
						$parens = key($parens);
						if ($this->debug === true) {
							$line = $tokens->line($parens);
							StatusWriter::write("* token has nested parenthesis $parens on line $line *", 1);
						}
					}

					$condition = 0;
					if (
						isset($rawTokens[$parenCloser]['conditions']) === true
						&& empty($rawTokens[$parenCloser]['conditions']) === false
						&& (isset($rawTokens[$parenCloser]['parenthesis_owner']) === false
						|| $parens > 0)
					) {
						$condition = $rawTokens[$parenCloser]['conditions'];
						end($condition);
						$condition = key($condition);
						if ($this->debug === true) {
							$line = $tokens->line($condition);
							$type = $rawTokens[$condition]['type'];
							StatusWriter::write("* token is inside condition $condition ($type) on line $line *", 1);
						}
					}

					if ($parens > $condition) {
						if ($this->debug === true) {
							StatusWriter::write('* using parenthesis *', 1);
						}

						$parenOpener = $parens;
						$condition   = 0;
					}
					elseif ($condition > 0) {
						if ($this->debug === true) {
							StatusWriter::write('* using condition *', 1);
						}

						$parenOpener = $condition;
						$parens      = 0;
					}

					$exact = false;

					$lastOpenTagConditions = array_keys($rawTokens[$lastOpenTag]['conditions']);
					$lastOpenTagCondition  = array_pop($lastOpenTagConditions);

					if ($condition > 0 && $lastOpenTagCondition === $condition) {
						if ($this->debug === true) {
							StatusWriter::write('* open tag is inside condition; using open tag *', 1);
						}

						$first = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $lastOpenTag, true);
						if ($this->debug === true) {
							$line = $tokens->line($first);
							$type = $rawTokens[$first]['type'];
							StatusWriter::write("* first token on line $line is $first ($type) *", 1);
						}

						$checkIndent = ($tokens->column($first) - 1);
						if (isset($adjustments[$condition]) === true) {
							$checkIndent += $adjustments[$condition];
						}

						$currentIndent = $checkIndent;

						if ($this->debug === true) {
							$type = $rawTokens[$lastOpenTag]['type'];
							StatusWriter::write("=> checking indent of $checkIndent; main indent set to $currentIndent by token $lastOpenTag ($type)", 1);
						}
					}
					elseif (
						$condition > 0
						&& $tokens->hasScopeOpener($condition)
						&& isset($setIndents[$tokens->scopeOpener($condition)])
					) {
						$checkIndent = $setIndents[$tokens->scopeOpener($condition)];
						if (isset($adjustments[$condition]) === true) {
							$checkIndent += $adjustments[$condition];
						}

						$currentIndent = $checkIndent;

						if ($this->debug === true) {
							$type = $rawTokens[$condition]['type'];
							StatusWriter::write("=> checking indent of $checkIndent; main indent set to $currentIndent by token $condition ($type)", 1);
						}
					}
					else {
						$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $parenOpener, true);

						$checkIndent = ($tokens->column($first) - 1);
						if (isset($adjustments[$first]) === true) {
							$checkIndent += $adjustments[$first];
						}

						if ($this->debug === true) {
							$line = $tokens->line($first);
							$type = $rawTokens[$first]['type'];
							StatusWriter::write("* first token on line $line is $first ($type) *", 1);
						}

						if (
							$first === $tokens->parenthesisOpener($parenCloser)
							&& $tokens->sameLine($first - 1, $first)
						) {
							// This is unlikely to be the start of the statement, so look
							// back further to find it.
							$first--;
							if ($this->debug === true) {
								$line = $tokens->line($first);
								$type = $rawTokens[$first]['type'];
								StatusWriter::write('* first token is the parenthesis opener *', 1);
								StatusWriter::write("* amended first token is $first ($type) on line $line *", 1);
							}
						}

						$prev = $phpcsFile->findStartOfStatement($first, T_COMMA);
						if ($prev !== $first) {
							// This is not the start of the statement.
							if ($this->debug === true) {
								$line = $tokens->line($prev);
								$type = $rawTokens[$prev]['type'];
								StatusWriter::write("* previous is $type on line $line *", 1);
							}

							$first = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $prev, true);
							if ($first !== false) {
								$prev  = $phpcsFile->findStartOfStatement($first, T_COMMA);
								$first = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $prev, true);
							}
							else {
								$first = $prev;
							}

							if ($this->debug === true) {
								$line = $tokens->line($first);
								$type = $rawTokens[$first]['type'];
								StatusWriter::write("* amended first token is $first ($type) on line $line *", 1);
							}
						}

						if (
							$tokens->hasScopeCloser($first)
							&& $tokens->scopeCloser($first) === $first
						) {
							if ($this->debug === true) {
								StatusWriter::write('* first token is a scope closer *', 1);
							}

							if (isset($rawTokens[$first]['scope_condition']) === true) {
								$scopeCloser = $first;
								$first       = $phpcsFile->findFirstOnLine(T_WHITESPACE, $rawTokens[$scopeCloser]['scope_condition'], true);

								$currentIndent = ($tokens->column($first) - 1);
								if (isset($adjustments[$first]) === true) {
									$currentIndent += $adjustments[$first];
								}

								// Make sure it is divisible by our expected indent.
								if ($tokens->code($rawTokens[$scopeCloser]['scope_condition']) !== T_CLOSURE) {
									$currentIndent = (int) (ceil($currentIndent / $this->indent) * $this->indent);
								}

								$setIndents[$first] = $currentIndent;

								if ($this->debug === true) {
									$type = $rawTokens[$first]['type'];
									StatusWriter::write("=> indent set to $currentIndent by token $first ($type)", 1);
								}
							}
						}
						else {
							// Don't force current indent to be divisible because there could be custom
							// rules in place between parenthesis, such as with arrays.
							$currentIndent = ($tokens->column($first) - 1);
							if (isset($adjustments[$first]) === true) {
								$currentIndent += $adjustments[$first];
							}

							$setIndents[$first] = $currentIndent;

							if ($this->debug === true) {
								$type = $rawTokens[$first]['type'];
								StatusWriter::write("=> checking indent of $checkIndent; main indent set to $currentIndent by token $first ($type)", 1);
							}
						}
					}
				}
				elseif ($this->debug === true) {
					StatusWriter::write(' * ignoring single-line definition *', 1);
				}
			}

			// Closing short array bracket should just be indented to at least
			// the same level as where it was opened (but can be more).
			if (
				$tokens->code($i) === T_CLOSE_SHORT_ARRAY
				|| ($checkToken !== null
				&& $tokens->code($checkToken) === T_CLOSE_SHORT_ARRAY)
			) {
				if ($checkToken !== null) {
					$arrayCloser = $checkToken;
				}
				else {
					$arrayCloser = $i;
				}

				if ($this->debug === true) {
					$line = $tokens->line($arrayCloser);
					StatusWriter::write("Closing short array bracket found on line $line");
				}

				$arrayOpener = $tokens->bracketOpener($arrayCloser);
				if (!$tokens->sameLine($arrayCloser, $arrayOpener)) {
					$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $arrayOpener, true);
					$exact = false;

					if ($this->debug === true) {
						$line = $tokens->line($first);
						$type = $rawTokens[$first]['type'];
						StatusWriter::write("* first token on line $line is $first ($type) *", 1);
					}

					if ($first === $tokens->bracketOpener($arrayCloser)) {
						// This is unlikely to be the start of the statement, so look
						// back further to find it.
						$first--;
					}

					$prev = $phpcsFile->findStartOfStatement($first, [T_COMMA, T_DOUBLE_ARROW]);
					if ($prev !== $first) {
						// This is not the start of the statement.
						if ($this->debug === true) {
							$line = $tokens->line($prev);
							$type = $rawTokens[$prev]['type'];
							StatusWriter::write("* previous is $type on line $line *", 1);
						}

						$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $prev, true);
						$prev  = $phpcsFile->findStartOfStatement($first, [T_COMMA, T_DOUBLE_ARROW]);
						$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $prev, true);
						if ($this->debug === true) {
							$line = $tokens->line($first);
							$type = $rawTokens[$first]['type'];
							StatusWriter::write("* amended first token is $first ($type) on line $line *", 1);
						}
					}
					elseif ($tokens->code($first) === T_WHITESPACE) {
						$first = $phpcsFile->findNext(T_WHITESPACE, ($first + 1), null, true);
					}

					$checkIndent = ($tokens->column($first) - 1);
					if (isset($adjustments[$first]) === true) {
						$checkIndent += $adjustments[$first];
					}

					if (
						$tokens->hasScopeCloser($first)
						&& $tokens->scopeCloser($first) === $first
					) {
						// The first token is a scope closer and would have already
						// been processed and set the indent level correctly, so
						// don't adjust it again.
						if ($this->debug === true) {
							StatusWriter::write('* first token is a scope closer; ignoring closing short array bracket *', 1);
						}

						if (isset($setIndents[$first]) === true) {
							$currentIndent = $setIndents[$first];
							if ($this->debug === true) {
								StatusWriter::write("=> indent reset to $currentIndent", 1);
							}
						}
					}
					else {
						// Don't force current indent to be divisible because there could be custom
						// rules in place for arrays.
						$currentIndent = ($tokens->column($first) - 1);
						if (isset($adjustments[$first]) === true) {
							$currentIndent += $adjustments[$first];
						}

						$setIndents[$first] = $currentIndent;

						if ($this->debug === true) {
							$type = $rawTokens[$first]['type'];
							StatusWriter::write("=> checking indent of $checkIndent; main indent set to $currentIndent by token $first ($type)", 1);
						}
					}
				}
				elseif ($this->debug === true) {
					StatusWriter::write(' * ignoring single-line definition *', 1);
				}
			}

			// Adjust lines within scopes while auto-fixing.
			if (
				$checkToken !== null
				&& $exact === false
				&& (empty($rawTokens[$checkToken]['conditions']) === false
				|| ($tokens->hasScopeOpener($checkToken)
				&& $tokens->scopeOpener($checkToken) === $checkToken))
			) {
				if (empty($rawTokens[$checkToken]['conditions']) === false) {
					$condition = $rawTokens[$checkToken]['conditions'];
					end($condition);
					$condition = key($condition);
				}
				else {
					$condition = $rawTokens[$checkToken]['scope_condition'];
				}

				$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $condition, true);

				if (
					isset($adjustments[$first]) === true
					&& (($adjustments[$first] < 0 && $tokenIndent > $currentIndent)
					|| ($adjustments[$first] > 0 && $tokenIndent < $currentIndent))
				) {
					$length = ($tokenIndent + $adjustments[$first]);

					// When fixing, we're going to adjust the indent of this line
					// here automatically, so use this new padding value when
					// comparing the expected padding to the actual padding.
					if ($phpcsFile->fixer->enabled === true) {
						$tokenIndent = $length;
						$this->adjustIndent($phpcsFile, $checkToken, $length, $adjustments[$first]);
					}

					if ($this->debug === true) {
						$line = $tokens->line($checkToken);
						$type = $rawTokens[$checkToken]['type'];
						StatusWriter::write("Indent adjusted to $length for $type on line $line");
					}

					$adjustments[$checkToken] = $adjustments[$first];

					if ($this->debug === true) {
						$line = $tokens->line($checkToken);
						$type = $rawTokens[$checkToken]['type'];
						StatusWriter::write('=> add adjustment of ' . $adjustments[$checkToken] . " for token $checkToken ($type) on line $line", 1);
					}
				}
			}

			// Scope closers reset the required indent to the same level as the opening condition.
			if (
				($checkToken !== null
				&& (isset($openScopes[$checkToken]) === true
				|| (isset($rawTokens[$checkToken]['scope_condition']) === true
				&& $tokens->hasScopeCloser($checkToken)
				&& $tokens->scopeCloser($checkToken) === $checkToken
				&& !$tokens->sameLine($checkToken, $tokens->scopeOpener($checkToken)))))
				|| ($checkToken === null
				&& isset($openScopes[$i]) === true)
			) {
				if ($this->debug === true) {
					if ($checkToken === null) {
						$type = $rawTokens[$rawTokens[$i]['scope_condition']]['type'];
						$line = $tokens->line($i);
					}
					else {
						$type = $rawTokens[$rawTokens[$checkToken]['scope_condition']]['type'];
						$line = $tokens->line($checkToken);
					}

					StatusWriter::write("Close scope ($type) on line $line");
				}

				$scopeCloser = $checkToken;
				if ($scopeCloser === null) {
					$scopeCloser = $i;
				}

				$conditionToken = array_pop($openScopes);
				if ($this->debug === true && $conditionToken !== null) {
					$line = $tokens->line($conditionToken);
					$type = $rawTokens[$conditionToken]['type'];
					StatusWriter::write("=> removed open scope $conditionToken ($type) on line $line", 1);
				}

				if (isset($rawTokens[$scopeCloser]['scope_condition']) === true) {
					$first = $phpcsFile->findFirstOnLine([T_WHITESPACE, T_INLINE_HTML], $rawTokens[$scopeCloser]['scope_condition'], true);
					if ($this->debug === true) {
						$line = $tokens->line($first);
						$type = $rawTokens[$first]['type'];
						StatusWriter::write("* first token is $first ($type) on line $line *", 1);
					}

					while (
						$tokens->code($first) === T_CONSTANT_ENCAPSED_STRING
						&& $tokens->code($first - 1) === T_CONSTANT_ENCAPSED_STRING
					) {
						$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, ($first - 1), true);
						if ($this->debug === true) {
							$line = $tokens->line($first);
							$type = $rawTokens[$first]['type'];
							StatusWriter::write("* found multi-line string; amended first token is $first ($type) on line $line *", 1);
						}
					}

					$currentIndent = ($tokens->column($first) - 1);
					if (isset($adjustments[$first]) === true) {
						$currentIndent += $adjustments[$first];
					}

					$setIndents[$scopeCloser] = $currentIndent;

					if ($this->debug === true) {
						$type = $rawTokens[$scopeCloser]['type'];
						StatusWriter::write("=> indent set to $currentIndent by token $scopeCloser ($type)", 1);
					}

					// We only check the indent of scope closers if they are
					// curly braces because other constructs tend to have different rules.
					if ($tokens->code($scopeCloser) === T_CLOSE_CURLY_BRACKET) {
						$exact = true;
					}
					else {
						$checkToken = null;
					}
				}
			}

			if (
				$checkToken !== null
				&& isset(Tokens::SCOPE_OPENERS[$tokens->code($checkToken)]) === true
				&& in_array($tokens->code($checkToken), $this->nonIndentingScopes, true) === false
				&& $tokens->hasScopeOpener($checkToken)
			) {
				$exact = true;

				if ($disableExactEnd > $checkToken) {
					foreach ($disableExactStack as $disableExactStackEnd) {
						if ($disableExactStackEnd < $checkToken) {
							continue;
						}

						if ($rawTokens[$checkToken]['conditions'] === $rawTokens[$disableExactStackEnd]['conditions']) {
							$exact = false;
							break;
						}
					}
				}

				$lastOpener = null;
				if (empty($openScopes) === false) {
					end($openScopes);
					$lastOpener = current($openScopes);
				}

				// A scope opener that shares a closer with another token (like multiple
				// CASEs using the same BREAK) needs to reduce the indent level so its
				// indent is checked correctly. It will then increase the indent again
				// (as all openers do) after being checked.
				if (
					$lastOpener !== null
					&& $tokens->hasScopeCloser($$lastOpener)
					&& $rawTokens[$lastOpener]['level'] === $rawTokens[$checkToken]['level']
					&& $tokens->scopeCloser($lastOpener) === $tokens->scopeCloser($checkToken)
				) {
					$currentIndent          -= $this->indent;
					$setIndents[$lastOpener] = $currentIndent;
					if ($this->debug === true) {
						$line = $tokens->line($i);
						$type = $rawTokens[$lastOpener]['type'];
						StatusWriter::write("Shared closer found on line $line");
						StatusWriter::write("=> indent set to $currentIndent by token $lastOpener ($type)", 1);
					}
				}

				if (
					$tokens->code($checkToken) === T_CLOSURE
					&& $tokenIndent > $currentIndent
				) {
					// The opener is indented more than needed, which is fine.
					// But just check that it is divisible by our expected indent.
					$checkIndent = (int) (ceil($tokenIndent / $this->indent) * $this->indent);
					$exact       = false;

					if ($this->debug === true) {
						$line = $tokens->line($i);
						StatusWriter::write("Closure found on line $line");
						StatusWriter::write("=> checking indent of $checkIndent; main indent remains at $currentIndent", 1);
					}
				}
			}

			// Method prefix indentation has to be exact or else it will break
			// the rest of the function declaration, and potentially future ones.
			if (
				$checkToken !== null
				&& isset(Tokens::METHOD_MODIFIERS[$tokens->code($checkToken)]) === true
				&& $tokens->code($checkToken + 1) !== T_DOUBLE_COLON
			) {
				$next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($checkToken + 1), null, true);
				if (
					$next === false
					|| ($tokens->code($next) !== T_CLOSURE
					&& $tokens->code($next) !== T_VARIABLE
					&& $tokens->code($next) !== T_FN)
				) {
					$isMethodPrefix = true;
					if (isset($rawTokens[$checkToken]['nested_parenthesis']) === true) {
						$parenthesis = array_keys($rawTokens[$checkToken]['nested_parenthesis']);
						$deepestOpen = array_pop($parenthesis);
						if (
							isset($rawTokens[$deepestOpen]['parenthesis_owner']) === true
							&& $rawTokens[$rawTokens[$deepestOpen]['parenthesis_owner']]['code'] === T_FUNCTION
						) {
							// This is constructor property promotion and not a method prefix.
							$isMethodPrefix = false;
						}
					}

					if ($isMethodPrefix === true) {
						if ($this->debug === true) {
							$line = $tokens->line($checkToken);
							$type = $rawTokens[$checkToken]['type'];
							StatusWriter::write("* method prefix ($type) found on line $line; indent set to exact *", 1);
						}

						$exact = true;
					}
				}
			}

			// Open PHP tags needs to be indented to exact column positions
			// so they don't cause problems with indent checks for the code
			// within them, but they don't need to line up with the current indent
			// in most cases.
			if (
				$checkToken !== null
				&& ($tokens->code($checkToken) === T_OPEN_TAG
				|| $tokens->code($checkToken) === T_OPEN_TAG_WITH_ECHO)
			) {
				$checkIndent = ($tokens->column($checkToken) - 1);

				// If we are re-opening a block that was closed in the same
				// scope as us, then reset the indent back to what the scope opener
				// set instead of using whatever indent this open tag has set.
				if (empty($rawTokens[$checkToken]['conditions']) === false) {
					$close = $phpcsFile->findPrevious(T_CLOSE_TAG, ($checkToken - 1));
					if (
						$close !== false
						&& $rawTokens[$checkToken]['conditions'] === $rawTokens[$close]['conditions']
					) {
						$conditions    = array_keys($rawTokens[$checkToken]['conditions']);
						$lastCondition = array_pop($conditions);
						$lastOpener    = $tokens->scopeOpener($lastCondition);
						$lastCloser    = $tokens->scopeCloser($lastCondition);
						if (
							!$tokens->sameLine($lastCloser, $checkToken)
							&& isset($setIndents[$lastOpener]) === true
						) {
							$checkIndent = $setIndents[$lastOpener];
						}
					}
				}
			}

			// Close tags needs to be indented to exact column positions.
			if ($checkToken !== null && $tokens->code($checkToken) === T_CLOSE_TAG) {
				$exact       = true;
				$checkIndent = $currentIndent;
				$checkIndent = (int) (ceil($checkIndent / $this->indent) * $this->indent);
			}

			// Special case for ELSE statements that are not on the same
			// line as the previous IF statements closing brace. They still need
			// to have the same indent or it will break code after the block.
			if ($checkToken !== null && $tokens->code($checkToken) === T_ELSE) {
				$exact = true;
			}

			// Don't perform strict checking on chained method calls since they
			// are often covered by custom rules.
			if (
				$checkToken !== null
				&& ($tokens->code($checkToken) === T_OBJECT_OPERATOR
				|| $tokens->code($checkToken) === T_NULLSAFE_OBJECT_OPERATOR)
				&& $exact === true
			) {
				$exact = false;
			}

			if ($checkIndent === null) {
				$checkIndent = $currentIndent;
			}

			/*
				The indent of the line is checked by the following IF block.

				Up until now, we've just been figuring out what the indent
				of this line should be.

				After this IF block, we adjust the indent again for
				the checking of future lines
			*/

			if (
				$checkToken !== null
				&& isset($this->ignoreIndentation[$tokens->code($checkToken)]) === false
				&& (($tokenIndent !== $checkIndent && $exact === true)
				|| ($tokenIndent < $checkIndent && $exact === false))
			) {
				$type  = 'IncorrectExact';
				$error = 'Line indented incorrectly; expected ';
				if ($exact === false) {
					$error .= 'at least ';
					$type   = 'Incorrect';
				}

				if ($this->tabIndent === true) {
					$expectedTabs = floor($checkIndent / $this->tabWidth);
					$foundTabs    = floor($tokenIndent / $this->tabWidth);
					$foundSpaces  = ($tokenIndent - ($foundTabs * $this->tabWidth));
					if ($foundSpaces > 0) {
						if ($foundTabs > 0) {
							$error .= '%s tabs, found %s tabs and %s spaces';
							$data   = [
								$expectedTabs,
								$foundTabs,
								$foundSpaces,
							];
						}
						else {
							$error .= '%s tabs, found %s spaces';
							$data   = [
								$expectedTabs,
								$foundSpaces,
							];
						}
					}
					else {
						$error .= '%s tabs, found %s';
						$data   = [
							$expectedTabs,
							$foundTabs,
						];
					}
				}
				else {
					$error .= '%s spaces, found %s';
					$data   = [
						$checkIndent,
						$tokenIndent,
					];
				}

				if ($this->debug === true) {
					$line    = $tokens->line($checkToken);
					$message = vsprintf($error, $data);
					StatusWriter::write("[Line $line] $message");
				}

				// Assume the change would be applied and continue
				// checking indents under this assumption. This gives more
				// technically accurate error messages.
				$adjustments[$checkToken] = ($checkIndent - $tokenIndent);

				$fix = $phpcsFile->addFixableError($error, $checkToken, $type, $data);
				if ($fix === true || $this->debug === true) {
					$accepted = $this->adjustIndent($phpcsFile, $checkToken, $checkIndent, ($checkIndent - $tokenIndent));

					if ($accepted === true && $this->debug === true) {
						$line = $tokens->line($checkToken);
						$type = $rawTokens[$checkToken]['type'];
						StatusWriter::write('=> add adjustment of ' . $adjustments[$checkToken] . " for token $checkToken ($type) on line $line", 1);
					}
				}
			}

			if ($checkToken !== null) {
				$i = $checkToken;
			}

			// Don't check indents exactly between arrays as they tend to have custom rules.
			if ($tokens->code($i) === T_OPEN_SHORT_ARRAY) {
				$disableExactStack[$tokens->bracketCloser($i)] = $tokens->bracketCloser($i);
				$disableExactEnd = max($disableExactEnd, $tokens->bracketCloser($i));
				if ($this->debug === true) {
					$line    = $tokens->line($i);
					$type    = $rawTokens[$disableExactEnd]['type'];
					$endLine = $tokens->line($disableExactEnd);
					StatusWriter::write("Opening short array bracket found on line $line");
					if ($disableExactEnd === $tokens->bracketCloser($i)) {
						StatusWriter::write("=> disabling exact indent checking until $disableExactEnd ($type) on line $endLine", 1);
					}
					else {
						StatusWriter::write("=> continuing to disable exact indent checking until $disableExactEnd ($type) on line $endLine", 1);
					}
				}
			}

			// Completely skip here/now docs as the indent is a part of the
			// content itself.
			if (
				$tokens->code($i) === T_START_HEREDOC
				|| $tokens->code($i) === T_START_NOWDOC
			) {
				if ($this->debug === true) {
					$line = $tokens->line($i);
					StatusWriter::write("Here/nowdoc found on line $line");
				}

				$i    = $phpcsFile->findNext([T_END_HEREDOC, T_END_NOWDOC], ($i + 1));
				$next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), null, true);
				if ($tokens->code($next) === T_COMMA) {
					$i = $next;
				}

				if ($this->debug === true) {
					$line = $tokens->line($i);
					$type = $rawTokens[$i]['type'];
					StatusWriter::write("* skipping to token $i ($type) on line $line *", 1);
				}

				continue;
			}

			// Completely skip multi-line strings as the indent is a part of the
			// content itself.
			if (
				$tokens->code($i) === T_CONSTANT_ENCAPSED_STRING
				|| $tokens->code($i) === T_DOUBLE_QUOTED_STRING
			) {
				$nextNonTextString = $phpcsFile->findNext($tokens->code($i), ($i + 1), null, true);
				if ($nextNonTextString !== false) {
					$i = ($nextNonTextString - 1);
				}

				continue;
			}

			// Completely skip doc comments as they tend to have complex
			// indentation rules.
			if ($tokens->code($i) === T_DOC_COMMENT_OPEN_TAG) {
				$i = $tokens->commentCloser($i);
				continue;
			}

			// Open tags reset the indent level.
			if (
				$tokens->code($i) === T_OPEN_TAG
				|| $tokens->code($i) === T_OPEN_TAG_WITH_ECHO
			) {
				if ($this->debug === true) {
					$line = $tokens->line($i);
					StatusWriter::write("Open PHP tag found on line $line");
				}

				if ($checkToken === null) {
					$first         = $phpcsFile->findFirstOnLine(T_WHITESPACE, $i, true);
					$currentIndent = (strlen($tokens->content($first)) - strlen(ltrim($tokens->content($first))));
				}
				else {
					$currentIndent = ($tokens->column($i) - 1);
				}

				$lastOpenTag = $i;

				if (isset($adjustments[$i]) === true) {
					$currentIndent += $adjustments[$i];
				}

				// Make sure it is divisible by our expected indent.
				$currentIndent  = (int) (ceil($currentIndent / $this->indent) * $this->indent);
				$setIndents[$i] = $currentIndent;

				if ($this->debug === true) {
					$type = $rawTokens[$i]['type'];
					StatusWriter::write("=> indent set to $currentIndent by token $i ($type)", 1);
				}

				continue;
			}

			// Close tags reset the indent level, unless they are closing a tag
			// opened on the same line.
			if ($tokens->code($i) === T_CLOSE_TAG) {
				if ($this->debug === true) {
					$line = $tokens->line($i);
					StatusWriter::write("Close PHP tag found on line $line");
				}

				if (!$tokens->sameLine($lastOpenTag, $i)) {
					$currentIndent = ($tokens->column($i) - 1);
					$lastCloseTag  = $i;
				}
				else {
					if ($lastCloseTag === null) {
						$currentIndent = 0;
					}
					else {
						$currentIndent = ($tokens->column($lastCloseTag) - 1);
					}
				}

				if (isset($adjustments[$i]) === true) {
					$currentIndent += $adjustments[$i];
				}

				// Make sure it is divisible by our expected indent.
				$currentIndent  = (int) (ceil($currentIndent / $this->indent) * $this->indent);
				$setIndents[$i] = $currentIndent;

				if ($this->debug === true) {
					$type = $rawTokens[$i]['type'];
					StatusWriter::write("=> indent set to $currentIndent by token $i ($type)", 1);
				}

				continue;
			}

			// Anon classes and functions set the indent based on their own indent level.
			if ($tokens->code($i) === T_CLOSURE || $tokens->code($i) === T_ANON_CLASS) {
				$closer = $tokens->scopeCloser($i);
				if ($tokens->sameLine($i, $closer)) {
					if ($this->debug === true) {
						$type = str_replace('_', ' ', strtolower(substr($rawTokens[$i]['type'], 2)));
						$line = $tokens->line($i);
						StatusWriter::write("* ignoring single-line $type on line $line *");
					}

					$i = $closer;
					continue;
				}

				if ($this->debug === true) {
					$type = str_replace('_', ' ', strtolower(substr($rawTokens[$i]['type'], 2)));
					$line = $tokens->line($i);
					StatusWriter::write("Open $type on line $line");
				}

				$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $i, true);
				if ($this->debug === true) {
					$line = $tokens->line($first);
					$type = $rawTokens[$first]['type'];
					StatusWriter::write("* first token is $first ($type) on line $line *", 1);
				}

				while (
					$tokens->code($first) === T_CONSTANT_ENCAPSED_STRING
					&& $tokens->code($first - 1) === T_CONSTANT_ENCAPSED_STRING
				) {
					$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, ($first - 1), true);
					if ($this->debug === true) {
						$line = $tokens->line($first);
						$type = $rawTokens[$first]['type'];
						StatusWriter::write("* found multi-line string; amended first token is $first ($type) on line $line *", 1);
					}
				}

				$currentIndent = (($tokens->column($first) - 1) + $this->indent);
				$openScopes[$tokens->scopeCloser($i)] = $tokens->scopeCondition($i);
				if ($this->debug === true) {
					$closerToken    = $tokens->scopeCloser($i);
					$closerLine     = $tokens->line($closerToken);
					$closerType     = $rawTokens[$closerToken]['type'];
					$conditionToken = $tokens->scopeCondition($i);
					$conditionLine  = $tokens->line($conditionToken);
					$conditionType  = $rawTokens[$conditionToken]['type'];
					StatusWriter::write("=> added open scope $closerToken ($closerType) on line $closerLine, pointing to condition $conditionToken ($conditionType) on line $conditionLine", 1);
				}

				if (isset($adjustments[$first]) === true) {
					$currentIndent += $adjustments[$first];
				}

				// Make sure it is divisible by our expected indent.
				$currentIndent = (int) (floor($currentIndent / $this->indent) * $this->indent);
				$i = $tokens->scopeOpener($i);
				$setIndents[$i] = $currentIndent;

				if ($this->debug === true) {
					$type = $rawTokens[$i]['type'];
					StatusWriter::write("=> indent set to $currentIndent by token $i ($type)", 1);
				}

				continue;
			}

			// Scope openers increase the indent level.
			if (
				$tokens->hasScopeCondition($i)
				&& $tokens->hasScopeOpener($i)
				&& $tokens->scopeOpener($i) === $i
			) {
				$closer = $tokens->scopeCloser($i);
				if ($tokens->sameLine($i, $closer)) {
					if ($this->debug === true) {
						$line = $tokens->line($i);
						$type = $rawTokens[$i]['type'];
						StatusWriter::write("* ignoring single-line $type on line $line *");
					}

					$i = $closer;
					continue;
				}

				$condition = $rawTokens[$rawTokens[$i]['scope_condition']]['code'];

				if (
					isset(Tokens::SCOPE_OPENERS[$condition]) === true
					&& in_array($condition, $this->nonIndentingScopes, true) === false
				) {
					if ($this->debug === true) {
						$line = $rawTokens[$i]['line'];
						$type = $rawTokens[$rawTokens[$i]['scope_condition']]['type'];
						StatusWriter::write("Open scope ($type) on line $line");
					}

					$currentIndent += $this->indent;
					$setIndents[$i] = $currentIndent;
					$openScopes[$rawTokens[$i]['scope_closer']] = $rawTokens[$i]['scope_condition'];
					if ($this->debug === true) {
						$closerToken    = $rawTokens[$i]['scope_closer'];
						$closerLine     = $rawTokens[$closerToken]['line'];
						$closerType     = $rawTokens[$closerToken]['type'];
						$conditionToken = $rawTokens[$i]['scope_condition'];
						$conditionLine  = $rawTokens[$conditionToken]['line'];
						$conditionType  = $rawTokens[$conditionToken]['type'];
						StatusWriter::write("=> added open scope $closerToken ($closerType) on line $closerLine, pointing to condition $conditionToken ($conditionType) on line $conditionLine", 1);
					}

					if ($this->debug === true) {
						$type = $rawTokens[$i]['type'];
						StatusWriter::write("=> indent set to $currentIndent by token $i ($type)", 1);
					}

					continue;
				}
			}

			// Closing an anon class, closure, match, or arrow function.
			// Each may be returned, which can confuse control structures that
			// use return as a closer, like CASE statements.
			if (
				isset($rawTokens[$i]['scope_condition']) === true
				&& $rawTokens[$i]['scope_closer'] === $i
				&& ($rawTokens[$rawTokens[$i]['scope_condition']]['code'] === T_CLOSURE
				|| $rawTokens[$rawTokens[$i]['scope_condition']]['code'] === T_ANON_CLASS
				|| $rawTokens[$rawTokens[$i]['scope_condition']]['code'] === T_MATCH
				|| $rawTokens[$rawTokens[$i]['scope_condition']]['code'] === T_FN)
			) {
				if ($this->debug === true) {
					$type = str_replace('_', ' ', strtolower(substr($rawTokens[$rawTokens[$i]['scope_condition']]['type'], 2)));
					$line = $rawTokens[$i]['line'];
					StatusWriter::write("Close $type on line $line");
				}

				$prev = false;

				$parens = 0;
				if (
					isset($rawTokens[$i]['nested_parenthesis']) === true
					&& empty($rawTokens[$i]['nested_parenthesis']) === false
				) {
					$parens = $rawTokens[$i]['nested_parenthesis'];
					end($parens);
					$parens = key($parens);
					if ($this->debug === true) {
						$line = $rawTokens[$parens]['line'];
						StatusWriter::write("* token has nested parenthesis $parens on line $line *", 1);
					}
				}

				$condition = 0;
				if (
					isset($rawTokens[$i]['conditions']) === true
					&& empty($rawTokens[$i]['conditions']) === false
				) {
					$condition = $rawTokens[$i]['conditions'];
					end($condition);
					$condition = key($condition);
					if ($this->debug === true) {
						$line = $rawTokens[$condition]['line'];
						$type = $rawTokens[$condition]['type'];
						StatusWriter::write("* token is inside condition $condition ($type) on line $line *", 1);
					}
				}

				if ($parens > $condition) {
					if ($this->debug === true) {
						StatusWriter::write('* using parenthesis *', 1);
					}

					$prev      = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($parens - 1), null, true);
					$condition = 0;
				}
				elseif ($condition > 0) {
					if ($this->debug === true) {
						StatusWriter::write('* using condition *', 1);
					}

					$prev   = $condition;
					$parens = 0;
				}

				if ($prev === false) {
					$prev = $phpcsFile->findPrevious([T_EQUAL, T_RETURN], ($rawTokens[$i]['scope_condition'] - 1), null, false, null, true);
					if ($prev === false) {
						$prev = $i;
						if ($this->debug === true) {
							StatusWriter::write('* could not find a previous T_EQUAL or T_RETURN token; will use current token *', 1);
						}
					}
				}

				if ($this->debug === true) {
					$line = $rawTokens[$prev]['line'];
					$type = $rawTokens[$prev]['type'];
					StatusWriter::write("* previous token is $type on line $line *", 1);
				}

				$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $prev, true);
				if ($this->debug === true) {
					$line = $rawTokens[$first]['line'];
					$type = $rawTokens[$first]['type'];
					StatusWriter::write("* first token on line $line is $first ($type) *", 1);
				}

				$prev = $phpcsFile->findStartOfStatement($first);
				if ($prev !== $first) {
					// This is not the start of the statement.
					if ($this->debug === true) {
						$line = $rawTokens[$prev]['line'];
						$type = $rawTokens[$prev]['type'];
						StatusWriter::write("* amended previous is $type on line $line *", 1);
					}

					$first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $prev, true);
					if ($this->debug === true) {
						$line = $rawTokens[$first]['line'];
						$type = $rawTokens[$first]['type'];
						StatusWriter::write("* amended first token is $first ($type) on line $line *", 1);
					}
				}

				$currentIndent = ($rawTokens[$first]['column'] - 1);
				if ($condition > 0) {
					$currentIndent += $this->indent;
				}

				if (
					isset($rawTokens[$first]['scope_closer']) === true
					&& $rawTokens[$first]['scope_closer'] === $first
				) {
					if ($this->debug === true) {
						StatusWriter::write('* first token is a scope closer *', 1);
					}

					if ($condition === 0 || $rawTokens[$condition]['scope_opener'] < $first) {
						$currentIndent = $setIndents[$first];
					}
					elseif ($this->debug === true) {
						StatusWriter::write('* ignoring scope closer *', 1);
					}
				}

				// Make sure it is divisible by our expected indent.
				$currentIndent      = (int) (ceil($currentIndent / $this->indent) * $this->indent);
				$setIndents[$first] = $currentIndent;

				if ($this->debug === true) {
					$type = $rawTokens[$first]['type'];
					StatusWriter::write("=> indent set to $currentIndent by token $first ($type)", 1);
				}
			}
		}

		// Don't process the rest of the file.
		return $phpcsFile->numTokens;
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 * @param int                         $length    The length of the new indent.
	 * @param int                         $change    The difference in length between
	 *                                               the old and new indent.
	 *
	 * @return bool
	 */
	protected function adjustIndent(File $phpcsFile, int $stackPtr, int $length, int $change)
	{
		$tokens = $phpcsFile->getTokens();

		// We don't adjust indents outside of PHP.
		if ($tokens[$stackPtr]['code'] === T_INLINE_HTML) {
			return false;
		}

		$padding = '';
		if ($length > 0) {
			if ($this->tabIndent === true) {
				$numTabs = floor($length / $this->tabWidth);
				if ($numTabs > 0) {
					$numSpaces = ($length - ($numTabs * $this->tabWidth));
					$padding   = str_repeat("\t", (int)$numTabs) . str_repeat(' ', (int)$numSpaces);
				}
			}
			else {
				$padding = str_repeat(' ', $length);
			}
		}

		if ($tokens[$stackPtr]['column'] === 1) {
			$trimmed  = ltrim($tokens[$stackPtr]['content']);
			$accepted = $phpcsFile->fixer->replaceToken($stackPtr, $padding . $trimmed);
		}
		else {
			// Easier to just replace the entire indent.
			$accepted = $phpcsFile->fixer->replaceToken(($stackPtr - 1), $padding);
		}

		if ($accepted === false) {
			return false;
		}

		if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_OPEN_TAG) {
			// We adjusted the start of a comment, so adjust the rest of it
			// as well so the alignment remains correct.
			for ($x = ($stackPtr + 1); $x < $tokens[$stackPtr]['comment_closer']; $x++) {
				if ($tokens[$x]['column'] !== 1) {
					continue;
				}

				$length = 0;
				if ($tokens[$x]['code'] === T_DOC_COMMENT_WHITESPACE) {
					$length = $tokens[$x]['length'];
				}

				$padding = ($length + $change);
				if ($padding > 0) {
					if ($this->tabIndent === true) {
						$numTabs   = floor($padding / $this->tabWidth);
						$numSpaces = ($padding - ($numTabs * $this->tabWidth));
						$padding   = str_repeat("\t", $numTabs) . str_repeat(' ', $numSpaces);
					}
					else {
						$padding = str_repeat(' ', $padding);
					}
				}
				else {
					$padding = '';
				}

				$phpcsFile->fixer->replaceToken($x, $padding);
				if ($this->debug === true) {
					$length = strlen($padding);
					$line   = $tokens[$x]['line'];
					$type   = $tokens[$x]['type'];
					StatusWriter::write("=> Indent adjusted to $length for $type on line $line", 1);
				}
			}
		}

		return true;
	}
}
