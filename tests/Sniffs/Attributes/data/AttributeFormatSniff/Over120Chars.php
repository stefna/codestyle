<?php declare(strict_types=1);

namespace StefnaTest\Sniffs\Attributes\data\AttributeFormatSniff;

class Over120Chars
{
	#[CourseWithAccount(studentId: '1ec1d34f-42ca-6004-aaf2-024218d0276e', courseId: 'a35b8b30-b11f-49a5-ba31-2192c4e893d3', withExam: true, progressId: 'b35b8b30-b11f-49a5-ba31-2192c4e893d4')]
	public function test(): void {}
}
