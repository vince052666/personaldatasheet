<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDS Version Retention
    |--------------------------------------------------------------------------
    |
    | Number of versions to retain for each PDS. Older versions will be
    | automatically pruned.
    |
    */
    'version_retention' => env('PDS_VERSION_RETENTION', 10),

    /*
    |--------------------------------------------------------------------------
    | Allow Multiple Active PDS
    |--------------------------------------------------------------------------
    |
    | Whether to allow users to have multiple active PDS records.
    |
    */
    'allow_multiple_active' => env('PDS_ALLOW_MULTIPLE_ACTIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Require Approval
    |--------------------------------------------------------------------------
    |
    | Whether PDS submissions require HR/Admin approval.
    |
    */
    'require_approval' => env('PDS_REQUIRE_APPROVAL', false),

    /*
    |--------------------------------------------------------------------------
    | Document Upload Settings
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size' => env('PDS_MAX_UPLOAD_SIZE', 10240), // KB
        'supported_formats' => explode(',', env('PDS_SUPPORTED_FORMATS', 'pdf,doc,docx,jpg,jpeg,png')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Parser Settings
    |--------------------------------------------------------------------------
    */
    'parser' => [
        'ocr_enabled' => env('PARSER_OCR_ENABLED', false),
        'ocr_service' => env('PARSER_OCR_SERVICE', 'tesseract'),
        'ocr_api_key' => env('PARSER_OCR_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Generator Settings
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'generator' => env('PDF_GENERATOR', 'dompdf'),
        'paper_size' => env('PDF_PAPER_SIZE', 'legal'),
        'orientation' => env('PDF_ORIENTATION', 'portrait'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excel Import Settings
    |--------------------------------------------------------------------------
    */
    'excel' => [
        'max_rows' => env('EXCEL_MAX_ROWS', 1000),
        'batch_size' => env('EXCEL_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        'anonymize_ip' => env('AUDIT_LOG_ANONYMIZE_IP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSC Form 212 Fields
    |--------------------------------------------------------------------------
    |
    | Define the structure of the Civil Service Commission Form 212
    |
    */
    'form_212_sections' => [
        'personal_information',
        'family_background',
        'educational_background',
        'civil_service_eligibility',
        'work_experience',
        'voluntary_work',
        'learning_and_development',
        'other_information',
    ],
];
