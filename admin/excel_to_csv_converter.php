<!DOCTYPE html>
<html>
<head>
    <title>Excel to CSV Converter - Clinic Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-area { border: 2px dashed #ccc; padding: 40px; text-align: center; margin: 20px 0; }
        .upload-area.dragover { border-color: #4F46E5; background-color: #f0f0ff; }
        .btn { background: #4F46E5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #3730A3; }
        .instructions { background: #f0f9ff; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel to CSV Converter</h1>
        
        <div class="instructions">
            <h3>How to Convert Excel to CSV:</h3>
            <ol>
                <li><strong>Method 1 (Recommended):</strong>
                    <ul>
                        <li>Open your Excel file (.xlsx)</li>
                        <li>Go to <strong>File → Save As</strong></li>
                        <li>Choose <strong>"CSV (Comma delimited)"</strong> format</li>
                        <li>Save the file</li>
                        <li>Upload the CSV file to the import page</li>
                    </ul>
                </li>
                <li><strong>Method 2 (Online):</strong>
                    <ul>
                        <li>Use online converters like <a href="https://convertio.co/xlsx-csv/" target="_blank">Convertio</a> or <a href="https://www.zamzar.com/convert/xlsx-to-csv/" target="_blank">Zamzar</a></li>
                        <li>Upload your Excel file</li>
                        <li>Download the converted CSV file</li>
                        <li>Upload the CSV file to the import page</li>
                    </ul>
                </li>
            </ol>
        </div>

        <h3>CSV Format Requirements:</h3>
        <p>Your CSV file must have at least these 8 columns in this exact order:</p>
        <code>student_id, name, dob, gender, address, civil_status, password, year_level</code>
        
        <p>Or use the full format with all 20 columns:</p>
        <code>student_id, name, dob, gender, address, email, parent_email, parent_phone, contact_number, religion, citizenship, course_program, civil_status, password, year_level, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, upload_year</code>

        <h3>Sample CSV Data:</h3>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
student_id,name,dob,gender,address,civil_status,password,year_level
SCC-22-00015336,Abella Joseph B.,3/19/2000,Male,Camarin Vito Minglanilla Cebu,Single,Abella,1st Year
SCC-22-00017358,Abellana Vincent Anthony Q.,7/8/2002,Male,Pakigne Minglanilla Cebu,Single,Abellana,1st Year
SCC-20-00010846,Abendan Christian James A.,4/27/2004,Male,Pob. Ward 2 Minglanilla Cebu,Single,Abendan,1st Year
        </pre>

        <div style="text-align: center; margin: 30px 0;">
            <a href="import.php" class="btn">Go to Import Page</a>
            <a href="csv_template.csv" download class="btn" style="background: #10B981; margin-left: 10px;">Download CSV Template</a>
        </div>

        <div class="instructions">
            <h3>Common Issues and Solutions:</h3>
            <ul>
                <li><strong>Excel file not working:</strong> Convert to CSV format first</li>
                <li><strong>Wrong column order:</strong> Make sure columns are in the exact order shown above</li>
                <li><strong>Missing data:</strong> Ensure student_id and name are not empty</li>
                <li><strong>Special characters:</strong> Avoid commas in your data, or wrap text in quotes</li>
                <li><strong>Encoding issues:</strong> Save your CSV file with UTF-8 encoding</li>
            </ul>
        </div>
    </div>
</body>
</html>
