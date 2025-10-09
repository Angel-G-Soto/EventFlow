<?php

namespace Tests\Unit;

use App\Support\Adapters\VenueCsvParser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;
use InvalidArgumentException;

/**
 * Unit test for the VenueCsvParser adapter.
 *
 * This test verifies the parser's logic in isolation, without booting the full Laravel application.
 * It uses a local stub CSV file to ensure predictable results.
 */
class VenueCsvParserTwoUnitTest extends TestCase
{
    /** @var VenueCsvParser */
    private $parser;

    /** @var string The path to the test CSV file. */
    private $validCsvPath;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VenueCsvParser();
        // Use __DIR__ to create a reliable path from the test file's location
        $this->validCsvPath = __DIR__ . '/Stubs/venues_test2.csv';
    }

    #[Test]
    public function it_successfully_parses_a_valid_csv_and_filters_by_lending_flag(): void
    {
        // Act: Call the parse method
        $venues = $this->parser->parse($this->validCsvPath);

        // Assert: Verify the output is as expected
        $this->assertIsArray($venues);
        
        // Based on the provided venues_test.csv, 5 rows have a room_code and allow_lending=1
        $this->assertCount(36, $venues, "The parser should have found exactly 27 valid venues to process.");

        // --- Assertions for the first processable venue ('TERRAZA') ---
        $firstVenue = $venues[0];
        $this->assertEquals('TERRAZA', $firstVenue['v_name']);
        $this->assertEquals('AE-344', $firstVenue['v_code']);
        $this->assertEquals('COLLEGE OF BUSINESS ADMINISTRATION', $firstVenue['department_name_raw']);
        $this->assertEquals(0, $firstVenue['v_capacity']);
        $this->assertEquals(0, $firstVenue['v_test_capacity'], "Test capacity should fall back to main capacity when empty.");
        $this->assertEquals('', $firstVenue['v_features']);
        $this->assertEquals('0000', $firstVenue['v_features_code']);

        // --- Assertions for the second processable venue ('UNIDAD DE PROGRAMACION') ---
        $secondVenue = $venues[1];
        $this->assertEquals('UNIDAD DE PROGRAMACION', $secondVenue['v_name']);
        $this->assertEquals('M-102', $secondVenue['v_code']);
        $this->assertEquals('CENTRO DE TECNOLOGIAS DE INFORMACION (CTI)', $secondVenue['department_name_raw']);
        $this->assertEquals(0, $secondVenue['v_capacity']);
        $this->assertEquals(0, $secondVenue['v_test_capacity'], "Test capacity should fall back to main capacity when empty.");
        $this->assertEquals('', $secondVenue['v_features']);
        $this->assertEquals('0000', $secondVenue['v_features_code']);

         // --- Assertions for the third processable venue ('SALON DE CLASES') ---
        $thirdVenue = $venues[2];
        $this->assertEquals('SALON DE CLASES', $thirdVenue['v_name']);
        $this->assertEquals('CM-201', $thirdVenue['v_code']);
        $this->assertEquals('ACTIVIDADES ATLETICAS', $thirdVenue['department_name_raw']);
        $this->assertEquals(30, $thirdVenue['v_capacity']);
        $this->assertEquals(30, $thirdVenue['v_test_capacity'], "Test capacity should fall back to main capacity when empty.");
        $this->assertEquals('Teaching Ready', $thirdVenue['v_features']);
        $this->assertEquals('0010', $thirdVenue['v_features_code']);
    }


    #[Test]
    public function it_throws_an_exception_for_a_non_existent_file(): void
    {
        try {
            // Act: Call the parse method with a path that does not exist
            $this->parser->parse(__DIR__ . '/Stubs/non_existent_file.csv');
            $this->fail('Expected an InvalidArgumentException to be thrown for a missing file.');
        } catch (InvalidArgumentException $e) {
            // Assert: Check that the correct exception type was caught.
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    #[Test]
    public function it_throws_an_exception_for_a_missing_required_header(): void
    {
        // Arrange: Create a temporary CSV file with a missing header
        $badCsvContent = "name,department_name,capacity\nSome Hall,Some Dept,100";
        $filePath = sys_get_temp_dir() . '/bad_header.csv';
        file_put_contents($filePath, $badCsvContent);

        try {
            // Act: Call the parse method
            $this->parser->parse($filePath);
            $this->fail('Expected an Exception to be thrown due to a missing header.');
        } catch (Exception $e) {
            // Assert: Check that the exception has the correct message.
            $this->assertEquals("Required CSV column 'room_code' is missing.", $e->getMessage());
        } finally {
            // Clean up the temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}

