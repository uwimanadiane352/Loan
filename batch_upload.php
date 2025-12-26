<?php
$results = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {

    $file = $_FILES["csv_file"]["tmp_name"];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Skip header

        $conn = new mysqli("localhost", "root", "", "loan_system");
        if ($conn->connect_error) { die("DB connection failed: ".$conn->connect_error); }

        while (($row = fgetcsv($handle)) !== FALSE) {
            $data = [
                "name" => $row[0],
                "annual_income" => (float)$row[1],
                "debt_to_income_ratio" => (float)$row[2],
                "credit_score" => (int)$row[3],
                "loan_amount" => (float)$row[4],
                "interest_rate" => (float)$row[5],
                "gender" => $row[6],
                "marital_status" => $row[7],
                "education_level" => $row[8],
                "employment_status" => $row[9],
                "loan_purpose" => $row[10],
                "grade_subgrade" => $row[11]
            ];

            // Call Flask API
            $ch = curl_init("http://127.0.0.1:5000/predict");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);

            if ($result && isset($result["prediction"])) {
                $stmt = $conn->prepare("
                    INSERT INTO predictions
                    (name, annual_income, debt_to_income_ratio, credit_score, loan_amount, interest_rate,
                     gender, marital_status, education_level, employment_status, loan_purpose,
                     grade_subgrade, prediction, approval_probability, rejection_probability)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sddiddsssssssdd",
                    $data["name"],
                    $data["annual_income"],
                    $data["debt_to_income_ratio"],
                    $data["credit_score"],
                    $data["loan_amount"],
                    $data["interest_rate"],
                    $data["gender"],
                    $data["marital_status"],
                    $data["education_level"],
                    $data["employment_status"],
                    $data["loan_purpose"],
                    $data["grade_subgrade"],
                    $result["prediction"],
                    $result["approval_probability"],
                    $result["rejection_probability"]
                );
                $stmt->execute();
                $stmt->close();

                // Add to results array for display
                $results[] = array_merge($data, $result);
            }
        }
        $conn->close();
        fclose($handle);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Batch Upload - Loan ML</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: Arial, sans-serif; }
.sidebar { height: 100vh; background: #212529; color: white; padding-top: 20px; }
.sidebar a { color: #dee2e6; text-decoration: none; padding: 10px 18px; display: block; border-radius: 8px; margin: 5px 10px; }
.sidebar a:hover, .sidebar a.active { background: #0d6efd; color: #fff; }
.content { padding: 20px; }
.card { border-radius: 12px; }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">

    <div class="col-md-3 col-lg-2 sidebar">
        <h5 class="text-center">Loan ML</h5>
        <hr class="text-secondary">
        <a href="index.php">🏠 Home</a>
        <a href="history.php">📜 History</a>
        <a href="batch_upload.php" class="active"><i class="bi bi-file-earmark-arrow-up"></i> Batch Upload</a>
        <a href="#">⚙️ Settings</a>
    </div>

    <div class="col-md-9 col-lg-10 content">
        <h5 class="mb-3">Batch Upload Loan Applications</h5>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Upload CSV File</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        <small class="text-muted">CSV must have columns: name, annual_income, debt_to_income_ratio, credit_score, loan_amount, interest_rate, gender, marital_status, education_level, employment_status, loan_purpose, grade_subgrade</small>
                    </div>
                    <button class="btn btn-primary">Upload & Predict</button>
                </form>
            </div>
        </div>

        <?php if(count($results) > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Income</th>
                            <th>Debt Ratio</th>
                            <th>Credit</th>
                            <th>Loan</th>
                            <th>Rate %</th>
                            <th>Gender</th>
                            <th>Marital</th>
                            <th>Education</th>
                            <th>Employment</th>
                            <th>Purpose</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Approval %</th>
                            <th>Rejection %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $i => $row): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= $row["name"] ?></td>
                            <td><?= number_format($row["annual_income"]) ?></td>
                            <td><?= $row["debt_to_income_ratio"] ?></td>
                            <td><?= $row["credit_score"] ?></td>
                            <td><?= number_format($row["loan_amount"]) ?></td>
                            <td><?= $row["interest_rate"] ?></td>
                            <td><?= $row["gender"] ?></td>
                            <td><?= $row["marital_status"] ?></td>
                            <td><?= $row["education_level"] ?></td>
                            <td><?= $row["employment_status"] ?></td>
                            <td><?= $row["loan_purpose"] ?></td>
                            <td><?= $row["grade_subgrade"] ?></td>
                            <td><?= $row["prediction"] ?></td>
                            <td><?= round($row["approval_probability"]*100,2) ?>%</td>
                            <td><?= round($row["rejection_probability"]*100,2) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
