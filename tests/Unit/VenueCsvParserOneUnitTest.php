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
 * 
 * This test will:
 * 1.  Confirm that a valid CSV is parsed correctly into the expected array structure.
 * 2.  Verify that your feature string and feature code logic are working.
 * 3.  Ensure the capacity fallback logic is correct.
 * 4.  Confirm that it gracefully skips invalid rows.
 * 5.  Prove that it throws the correct exceptions for a missing file or a malformed CSV, ensuring your error handling is robust.
 */
class VenueCsvParserOneUnitTest extends TestCase
{
    /** @var VenueCsvParser */
    private $parser;

    /** @var string The path to the test CSV file. */
    private $validCsvPath;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VenueCsvParser();
        // Use __DIR__ to create a reliable path from the test file's location
        $this->validCsvPath = __DIR__ . '/Stubs/venue_test.csv';
    }

    #[Test]
    public function it_successfully_parses_a_valid_csv_file(): void
    {
        // Act: Call the parse method
        $venues = $this->parser->parse($this->validCsvPath);

        // Assert: Verify the output is as expected
        $this->assertIsArray($venues);
        $this->assertCount(3, $venues, "The parser should have processed 3 valid rows and skipped 1 invalid row.");

        // --- Assertions for the first venue (Main Auditorium) ---
        $firstVenue = $venues[0];
        $this->assertEquals('Main Auditorium', $firstVenue['v_name']);
        $this->assertEquals('AUD-101', $firstVenue['v_code']);
        $this->assertEquals('Arts and Sciences', $firstVenue['department_name_raw']);
        $this->assertEquals(150, $firstVenue['v_capacity']);
        $this->assertEquals(120, $firstVenue['v_test_capacity']);
        $this->assertEquals('Multimedia Enabled', $firstVenue['v_features']);
        $this->assertEquals('1010', $firstVenue['v_features_code']);

        // --- Assertions for the second venue (Computer Lab) ---
        $secondVenue = $venues[1];
        $this->assertEquals('Computer Lab', $secondVenue['v_name']);
        $this->assertEquals('COMP-202', $secondVenue['v_code']);
        $this->assertEquals('1101', $secondVenue['v_features_code']);

        // --- Assertions for the third venue (Lecture Hall) to test capacity fallback ---
        $thirdVenue = $venues[2];
        $this->assertEquals('Lecture Hall', $thirdVenue['v_name']);
        $this->assertEquals(80, $thirdVenue['v_capacity']);
        $this->assertEquals(80, $thirdVenue['v_test_capacity'], "Test capacity should fall back to main capacity when 0.");
        $this->assertEquals('0110', $thirdVenue['v_features_code']);
    }

    #[Test]
    public function it_throws_an_exception_for_a_non_existent_file(): void
    {
        // Arrange: A path to a file that does not exist
        $filePath = __DIR__ . '/Stubs/non_existent_file.csv';

        try {
            // Act: Call the parse method
            $this->parser->parse($filePath);
            // This line should not be reached; if it is, the test fails.
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
            // This line should not be reached; if it is, the test fails.
            $this->fail('Expected an Exception to be thrown due to a missing header.');
        } catch (Exception $e) {
            // Assert: Check that the exception is the correct type and has the correct message.
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals("Required CSV column 'room_code' is missing.", $e->getMessage());
        } finally {
            // Clean up the temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
