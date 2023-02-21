<?php

/**
 * Class FinalResult
 * 
 * @copyright (c) 2023 - Hivelocity inc, All Right Reserved.
 * 
 */
class FinalResult
{
    const CSV_HEADER = [
        'currency' => 0,
        'failure_code' => 1,
        'failure_message' => 2
    ];

    const CSV_BODY = [
        'bank_code' => 0,
        'bank_branch_code' => 2,
        'bank_account_number' => 6,
        'bank_account_name' => 7,
        'amount' => 8,
        'first_end_to_end_id' => 10,
        'last_end_to_end_id' => 11
    ];

    /**
     * Retrieve data from CSV file.
     * 
     * @param string $filePath
     * 
     * @return array
     */
    public function results(string $fileName)
    {
        if (!file_exists($fileName)) {
            throw new Exception('File not found.');
        }

        $csvFile = fopen($fileName, "r");
        if (!$csvFile) {
            throw new Exception('File open failed.');
        }

        $header = fgetcsv($csvFile);
        if (!$header) {
            throw new Exception('Fail to read the header info.');
        }

        $records = $this->getRecordsFromCsvFile($header, $csvFile);

        fclose($csvFile);

        return [
            "filename" => basename($fileName),
            "document" => $csvFile,
            "failure_code" => $header[self::CSV_HEADER['failure_code']],
            "failure_message" => $header[self::CSV_HEADER['failure_message']],
            "records" => $records
        ];
    }

    /**
     * Get records from CSV file.
     * 
     * @param $header,
     * @param $csvFile
     * 
     * @return array
     */
    private function getRecordsFromCsvFile($header, $csvFile)
    {
        $records = [];
        while (!feof($csvFile)) {
            $row = fgetcsv($csvFile);
            if (count($row) === 16) {
                $records[] = $this->getRecordFromCsvFile($header, $row);
            }
        }

        return $records;
    }

    /**
     * Get record from csv file.
     * 
     * @param array $header
     * @param array $row
     * 
     * @return array
     */
    private function getRecordFromCsvFile($header, $row)
    {
        $amount = $this->getAmount($row);
        $bankAccountNumber = $this->getBankAccountNumber($row);
        $bankBranchCode = $this->getBankBranchCode($row);
        $endToEndID = $this->getEndToEndID($row);

        return [
            "amount" => [
                "currency" => $header[self::CSV_HEADER['currency']],
                "subunits" => (int) ($amount * 100)
            ],
            "bank_account_name" => str_replace(" ", "_", strtolower($row[self::CSV_BODY['bank_account_name']])),
            "bank_account_number" => $bankAccountNumber,
            "bank_branch_code" => $bankBranchCode,
            "bank_code" => $row[self::CSV_BODY['bank_code']],
            "end_to_end_id" => $endToEndID,
        ];
    }

    /**
     * Get the amount.
     * 
     * @param $row
     * 
     * @return int|float
     */
    private function getAmount(array $row)
    {
        return (!$row[self::CSV_BODY['amount']] || $row[self::CSV_BODY['amount']] === "0") ? 0 : (float) $row[self::CSV_BODY['amount']];
    }

    /**
     * Get the Bank account number.
     * 
     * @param array $row
     * 
     * @return int|string
     */
    private function getBankAccountNumber(array $row)
    {
        return !$row[self::CSV_BODY['bank_account_number']] ? "Bank account number missing" : (int) $row[self::CSV_BODY['bank_account_number']];
    }

    /**
     * Get the bank branch code.
     * 
     * @param array $row
     * 
     * @return int|string
     */
    private function getBankBranchCode(array $row)
    {
        return !$row[self::CSV_BODY['bank_branch_code']] ? "Bank branch code missing" : $row[self::CSV_BODY['bank_branch_code']];
    }

    /**
     * Get the end to end id.
     * 
     * @param array $row
     * 
     * @return string
     */
    private function getEndToEndID(array $row)
    {
        return !$row[self::CSV_BODY['first_end_to_end_id']] && !$row[self::CSV_BODY['last_end_to_end_id']] ? "End to end id missing" : $row[self::CSV_BODY['first_end_to_end_id']] . $row[self::CSV_BODY['last_end_to_end_id']];
    }
}
