#!/usr/bin/env bash

if [ -e .git/hooks/pre-commit ];
then
    PRE_COMMIT_EXISTS=1
else
    PRE_COMMIT_EXISTS=0
fi

SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  BIN_DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null && pwd )"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$BIN_DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
BIN_DIR="$( cd -P "$( dirname "$SOURCE" )" >/dev/null && pwd )"
REPO_DIR="$(dirname "$BIN_DIR")"

$(sed -e "s|@@CODESTYLE_PATH@@|$REPO_DIR/|g" "${REPO_DIR}/git-hooks/library-pre-commit.sh" > .git/hooks/pre-commit)
chmod +x .git/hooks/pre-commit

if [ "$PRE_COMMIT_EXISTS" = 0 ];
then
    echo "Pre-commit git hook is installed!"
else
    echo "Pre-commit git hook is updated!"
fi
