<?php

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use voku\helper\ASCII;

require __DIR__ . '/vendor/autoload.php';

function translateText($text, $targetLanguage, $apiKey)
{
    $url = 'https://translation.googleapis.com/language/translate/v2?key=' . $apiKey;

    $data = array(
        'q' => $text,
        'target' => $targetLanguage,
        'format' => 'text'
    );

    $jsonData = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $response = curl_exec($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);
    return $decodedResponse['data']['translations'][0]['translatedText'];
}

function translateXLSX()
{
    $apiKey = 'api_key';
    $targetLanguage = 'ukr';

    ini_set('memory_limit', '2048M');
    $inputFileName = 'ukr_data.csv';
    $outputFileName = 'ukr_rus_data.csv';

    $spreadsheet = IOFactory::load($inputFileName);

    $sheet = $spreadsheet->getActiveSheet();

    $nameColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString('B');
    $newColumnIndex = $nameColumnIndex + 1;
    $sheet->insertNewColumnBefore(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($newColumnIndex), 1);

    $sheet->setCellValueByColumnAndRow($newColumnIndex, 1, 'ukr_name');

    $rowCount = $sheet->getHighestRow();
    $arr = [];
    $counter = 0;
    $exploderNum = 5;

    for ($row = 2; $row <= $rowCount; ++$row) {

        $valueToTranslate = $sheet->getCellByColumnAndRow($nameColumnIndex, $row)->getValue();
        $arr[] = $valueToTranslate;
        $counter++;

        if ($counter === $exploderNum) {

            $counter = 0;
            $resultString = implode("; ", $arr) . ';';
            $translatedValue = translateText($resultString, $targetLanguage, $apiKey);
            $arr = [];
            $array = explode(";", $translatedValue);
            $array = array_filter(array_map('trim', $array));
            $newRow = $row - $exploderNum;

            for ($rowCounter = 0; $rowCounter < count($array); $rowCounter++) {

                $newRow++;
                $sheet->setCellValueByColumnAndRow($newColumnIndex, $newRow, $array[$rowCounter]);

            }
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($outputFileName);

    echo "Done!";


}

function translateCSV()
{
    $apiKey = 'api-key';
    $targetLanguage = 'en';

    ini_set('memory_limit', '2048M');
    $inputFileName = 'rus_ukr_data.csv';
    $outputFileName = 'ukr_rus_eng_data.csv';

    $csv = Reader::createFromPath($inputFileName, 'r');
    $csv->setDelimiter(',');
    $csv->setHeaderOffset(0);

    $stmt = Statement::create();
    $records = $stmt->process($csv);
    $allRecords = iterator_to_array($records);

    $writer = Writer::createFromPath($outputFileName, 'w+');
    $writer->setDelimiter(',');

    $counter = 0;
    $exploderNum = 60;
    $arr = [];
    $headerWritten = false;

    foreach ($allRecords as $offset => $record) {
        if ($counter === 0 && !$headerWritten) {
            $headers = array_keys($record);
            array_splice($headers, 2, 0, 'название_англ');
            $writer->insertOne($headers);
            $headerWritten = true;
        }

        $arr[] = $record['Название_укр'];
        $counter++;

        if ($counter === $exploderNum || $offset + 1 === count($allRecords)) {
            $counter = 0;
            $resultString = implode("; ", $arr) . ';';
            $translatedValue = translateText($resultString, $targetLanguage, $apiKey);
            $arr = [];
            $array = explode(";", $translatedValue);
            $array = array_filter(array_map('trim', $array));

            foreach ($array as $key => $value) {
                if (isset($allRecords[$offset - count($array) + $key + 1])) {
                    $record = $allRecords[$offset - count($array) + $key + 1];
                    array_splice($record, 2, 0, $value);
                    $writer->insertOne($record);
                }
            }
        }
    }

    echo "Done!";
}

function seed()
{
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "calories";
    $tablename = "products";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $csvFile = fopen('ukr_rus_eng_data.csv', 'r');

    $headers = fgetcsv($csvFile, 1000, ",");

    $query = "INSERT INTO $tablename (name_ukr, name_rus, name_eng, calories, proteins, carbohydrates, fats, fibers) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
        $name_ukr = trim($row[0]);
        $name_rus = trim($row[1]);
        $name_eng = trim($row[2]);
        $calories = (float)trim($row[3]);
        $proteins = (float)trim($row[4]);
        $carbohydrates = (float)trim($row[5]);
        $fats = (float)trim($row[6]);
        $fibers = !empty(trim($row[7])) ? (float)trim($row[7]) : null;

        $stmt->bind_param("sssddddd", $name_ukr, $name_rus, $name_eng, $calories, $proteins, $carbohydrates, $fats, $fibers);
        $stmt->execute();
    }

    $stmt->close();
    fclose($csvFile);

    $conn->close();

    echo "Data imported successfully.";
}

function matches($firtsTable, $secondTable)
{
    function readCsv($filename)
    {
        $rows = array();
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    $firtsTable_data = readCsv($firtsTable);
    $secondTable_data = readCsv($secondTable);

    $matches = [];
    foreach ($firtsTable_data as $firtsTable_index => $firtsTable_row) {
        foreach ($secondTable_data as $secondTable_row) {

            if (
                $firtsTable_row['Энергия'] == $secondTable_row['Энергия'] &&
                $firtsTable_row['Белки'] == $secondTable_row['Белки'] &&
                $firtsTable_row['Углеводы'] == $secondTable_row['Углеводы'] &&
                $firtsTable_row['Жиры'] == $secondTable_row['Жиры'] &&
                $firtsTable_row['Волокна'] == $secondTable_row['Волокна'] &&
                similarity($firtsTable_row['название'], $secondTable_row['Название'])
            ) {

                $matches[] = [
                    'rus_ukr_name' => $firtsTable_row['название'],
                    'rus_name' => $secondTable_row['Название'],
                ];
                unset($firtsTable_data[$firtsTable_index]);
            }
        }
    }
    $outputHandle = fopen($firtsTable, 'w');
    $header = array_keys(['Название', 'название', 'Энергия', 'Белки', 'Углеводы', 'Жиры', 'Волокна']);
    fputcsv($outputHandle, $header);
    foreach ($firtsTable_data as $row) {
        fputcsv($outputHandle, $row);
    }
    fclose($outputHandle);

//    $matchesCounter = 0;
//    foreach ($matches as $match) {
//        $matchesCounter++;
////        dump("UKR: " . $match['rus_ukr_name'] . ", RUS: " . $match['rus_name']);
//    }
//    dump($matchesCounter);

//    foreach ($firtsTable_data as $row) {
//        dump($row);
//        $matchesCounter++;
//    }
//    dump($matchesCounter);
}

function similarity($str1, $str2)
{
    $similarity = calculateSimilarity($str1, $str2);
    if ($similarity >= 10 && $similarity <= 100) {
        return true;
    } else {
        $words1 = explode(' ', $str1);

        if (count($words1) == 2) {
            $str1_reversed = implode(' ', array_reverse($words1));

            $similarity = calculateSimilarity($str1_reversed, $str2);
            return ($similarity >= 10 && $similarity <= 100);
        }
        return false;
    }
}

function calculateSimilarity($str1, $str2)
{
    $len1 = mb_strlen($str1, 'UTF-8');
    $len2 = mb_strlen($str2, 'UTF-8');

    $maxLen = max($len1, $len2);
    if ($maxLen == 0) {
        return 100;
    }

    $levDist = levenshtein($str1, $str2);
    return (1 - $levDist / $maxLen) * 100;
}

function cellsConvertation()
{
    $inputFileName = 'rus_data.csv';
    $outputFileName = 'converted_rus_data.csv';

    function convertValue($value)
    {
        $value = str_replace(',', '.', $value);
        $value = trim($value, '"');
        return $value;
    }

    if (($handle = fopen($inputFileName, "r")) !== FALSE) {
        $rows = [];
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rows[] = array_map('convertValue', $data);
        fclose($handle);

        $outputHandle = fopen($outputFileName, 'w');
        foreach ($rows as $row) {
            fputcsv($outputHandle, $row);
        }
        fclose($outputHandle);
    }

    echo "Done!";
}

function changeNames()
{
    $inputFileName = 'converted_ukr_rus_data.csv';
    $outputFileName = 'wrapped_converted_ukr_rus_data.csv';

    if (($handle = fopen($inputFileName, "r")) !== FALSE) {
        $rows = [];
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $temp = $data[0];
            $data[0] = $data[1];
            $data[1] = $temp;

            $rows[] = $data;
        }
        fclose($handle);

        $outputHandle = fopen($outputFileName, 'w');
        foreach ($rows as $row) {
            fputcsv($outputHandle, $row);
        }
        fclose($outputHandle);
    }

    echo "Done!";
}

//changeNames();
//cellsConvertation();
//translateCSV();
//seed();
function updateTransliteratedNamesUsingASCII()
{
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "calories";
    $tablename = "product_translations";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, name FROM $tablename";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transliteratedName = ASCII::to_transliterate($row['name']);
            $updateSql = "UPDATE $tablename SET transliterated_name=? WHERE id=?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $transliteratedName, $row['id']);
            $stmt->execute();
        }
        echo "All names have been successfully transliterated.";
    } else {
        echo "No records found.";
    }

    $conn->close();
}


function RoflsDoubleMetaphone()
{
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "calories";
    $tablename = "product_translations";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, transliterated_name FROM $tablename";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $words = explode(' ', $row['transliterated_name']);
            $encodedWords = [];

            foreach ($words as $word) {
                if (!empty($word)) {
                    $doubleMetaphone = soundex($word);
                    $encodedWords[] = $doubleMetaphone;
                }
            }

            $transliteratedName = implode(' ', $encodedWords);

            $updateSql = "UPDATE $tablename SET soundexed_name=? WHERE id=?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $transliteratedName, $row['id']);
            $stmt->execute();
        }

        echo "All names have been successfully transliterated.";
    } else {
        echo "No records found.";
    }

    $conn->close();
}

