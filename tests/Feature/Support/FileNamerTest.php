<?php

namespace Tests\Feature\Support;

use App\Support\FileNamer;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileNamerTest extends TestCase
{
    public function test_prefix_is_the_uppercased_tenant_key_never_hardcoded()
    {
        config(['app.tenant' => 'persol']);
        $this->assertSame('PERSOL', FileNamer::prefix());

        config(['app.tenant' => 'fateh']);
        $this->assertSame('FATEH', FileNamer::prefix());
    }

    public function test_stored_name_has_tenant_module_date_random_and_never_the_original_name()
    {
        config(['app.tenant' => 'persol']);

        $name = FileNamer::stored('dms', UploadedFile::fake()->create('Secret Budget.XLSX', 5));

        $this->assertMatchesRegularExpression('/^PERSOL-DMS-\d{8}-[A-Za-z0-9]{12}-\d+\.[A-Za-z0-9]+$/', $name);
        $this->assertStringNotContainsString('Secret', $name);
        $this->assertNotSame($name, FileNamer::stored('dms', UploadedFile::fake()->create('Secret Budget.XLSX', 5)));
    }

    public function test_download_name_is_ascii_safe_and_skips_blank_parts()
    {
        config(['app.tenant' => 'persol']);

        $this->assertSame('PERSOL-PRC-014-v2-excel-2.xlsx', FileNamer::download('XLSX', 'PRC/014', 'v2', 'excel', 2));
        $this->assertSame('PERSOL-PRC-014-pdf.pdf', FileNamer::download('pdf', 'PRC 014', null, 'pdf', ''));
    }
}
