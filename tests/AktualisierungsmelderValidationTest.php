<?php

declare(strict_types=1);

namespace tests;

use TestCaseSymconValidation;

include_once __DIR__ . '/stubs/Validator.php';

class AktualisierungsmelderValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule_Aktualisierungsmelder(): void
    {
        $this->validateModule(__DIR__ . '/../Aktualisierungsmelder');
    }
}