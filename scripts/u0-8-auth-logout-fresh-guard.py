from pathlib import Path

exporter = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = exporter.read_text()
old = """        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));
        $this->assertSame((string) $user->getKey(), (string) User::query()->findOrFail($user->getKey())->getKey());

        $this->withToken($plainTextToken)
"""
new = """        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));
        $this->assertSame((string) $user->getKey(), (string) User::query()->findOrFail($user->getKey())->getKey());

        app('auth')->forgetGuards();

        $this->withToken($plainTextToken)
"""
if new not in text:
    if old not in text:
        raise SystemExit('missing generated logout test marker')
    text = text.replace(old, new, 1)
exporter.write_text(text)

root_test = Path('tests/Feature/GeneratedAuthLogoutVerticalSliceExportTest.php')
text = root_test.read_text()
old = """        $this->assertStringContainsString('assertNull(PersonalAccessToken::findToken', $test);

        $this->assertIsString($openApi);
"""
new = """        $this->assertStringContainsString('assertNull(PersonalAccessToken::findToken', $test);
        $this->assertStringContainsString("app('auth')->forgetGuards();", $test);

        $this->assertIsString($openApi);
"""
if new not in text:
    if old not in text:
        raise SystemExit('missing root logout test marker')
    text = text.replace(old, new, 1)
root_test.write_text(text)

print('fresh guard acceptance patch applied')
