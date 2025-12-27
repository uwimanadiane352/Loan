<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Initialize result variables
$result = null;
$error_message = "";
$data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = [
        "name" => isset($_POST["name"]) ? trim((string)$_POST["name"]) : "Unknown",
        "annual_income" => (float)$_POST["annual_income"],
        "debt_to_income_ratio" => (float)$_POST["debt_to_income_ratio"],
        "credit_score" => (int)$_POST["credit_score"],
        "loan_amount" => (float)$_POST["loan_amount"],
        "interest_rate" => (float)$_POST["interest_rate"],
        "gender" => $_POST["gender"],
        "marital_status" => $_POST["marital_status"],
        "education_level" => $_POST["education_level"],
        "employment_status" => $_POST["employment_status"],
        "loan_purpose" => $_POST["loan_purpose"],
        "grade_subgrade" => $_POST["grade_subgrade"]
    ];

    // Call Flask API
    $ch = curl_init("http://127.0.0.1:5000/predict");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        $error_message = "Curl Error: $curl_error";
    } else {
        $result = json_decode($response, true);
        if (!$result) {
            $error_message = "Invalid JSON from ML API: $response";
        } elseif (isset($result["error"])) {
            $error_message = "ML API Error: ".$result["error"];
        } else {
            // Save to DB
            $conn = new mysqli("localhost", "root", "", "loan_system1");
            if ($conn->connect_error) {
                $error_message = "DB connection failed";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO predictions
                    (name, annual_income, debt_to_income_ratio, credit_score, loan_amount, interest_rate,
                     gender, marital_status, education_level, employment_status, loan_purpose,
                     grade_subgrade, prediction, approval_probability, rejection_probability)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sddiddsssssssdd",
                    $data["name"], $data["annual_income"], $data["debt_to_income_ratio"],
                    $data["credit_score"], $data["loan_amount"], $data["interest_rate"],
                    $data["gender"], $data["marital_status"], $data["education_level"],
                    $data["employment_status"], $data["loan_purpose"], $data["grade_subgrade"],
                    $result["prediction"], $result["approval_probability"], $result["rejection_probability"]
                );
                $stmt->execute();
                $stmt->close();
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Loan Prediction Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: Arial, sans-serif; }
.sidebar { height: 100vh; background: #212529; color: white; padding-top: 20px; }
.sidebar a { color: #dee2e6; text-decoration: none; padding: 10px 18px; display: block; border-radius: 8px; margin: 5px 10px; }
.sidebar a:hover, .sidebar a.active { background: #0d6efd; color: #fff; }
.batch-btn { display: flex; align-items: center; gap: 8px; background: #495057; transition: 0.3s; }
.batch-btn:hover { background: #0d6efd; color: #fff; }
.content { padding: 20px; }
.card { border-radius: 12px; }
.result-card { margin-top: 20px; }
.circle {
    width: 150px; height: 150px; border-radius: 50%; display: inline-block; 
    line-height: 150px; text-align: center; font-weight: bold; font-size: 20px; color:#212529;
}
.circle-approval { background: conic-gradient(#198754 0deg 0deg, #e9ecef 0deg 360deg); }
.circle-rejection { background: conic-gradient(#dc3545 0deg 0deg, #e9ecef 0deg 360deg); }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">

    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 sidebar">
        <h5 class="text-center">Loan ML</h5>
        <hr class="text-secondary">
        <a href="index.php" class="active">🏠 Home</a>
        <a href="history.php">📜 History</a>
        <a href="batch_upload.php" class="batch-btn"><i class="bi bi-file-earmark-arrow-up"></i> Batch Upload</a>
        <a href="logout.php" class="batch-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 content">
        <h5 class="mb-3">Loan Approval Prediction</h5>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <?php if($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Applicant Name</label><input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($data['name'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Annual Income</label><input type="number" class="form-control" name="annual_income" required value="<?= htmlspecialchars($data['annual_income'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Debt Ratio</label><input type="number" step="0.01" class="form-control" name="debt_to_income_ratio" required value="<?= htmlspecialchars($data['debt_to_income_ratio'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Credit Score</label><input type="number" class="form-control" name="credit_score" required value="<?= htmlspecialchars($data['credit_score'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Loan Amount</label><input type="number" class="form-control" name="loan_amount" required value="<?= htmlspecialchars($data['loan_amount'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.01" class="form-control" name="interest_rate" required value="<?= htmlspecialchars($data['interest_rate'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Gender</label><select class="form-select" name="gender">
                            <option <?= (isset($data['gender']) && $data['gender']=='Male')?'selected':'' ?>>Male</option>
                            <option <?= (isset($data['gender']) && $data['gender']=='Female')?'selected':'' ?>>Female</option>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Marital Status</label><select class="form-select" name="marital_status">
                            <option <?= (isset($data['marital_status']) && $data['marital_status']=='Single')?'selected':'' ?>>Single</option>
                            <option <?= (isset($data['marital_status']) && $data['marital_status']=='Married')?'selected':'' ?>>Married</option>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Education</label><select class="form-select" name="education_level">
                            <option <?= (isset($data['education_level']) && $data['education_level']=='Highschool')?'selected':'' ?>>Highschool</option>
                            <option <?= (isset($data['education_level']) && $data['education_level']=='Bachelor')?'selected':'' ?>>Bachelor</option>
                            <option <?= (isset($data['education_level']) && $data['education_level']=='Master')?'selected':'' ?>>Master</option>
                            <option <?= (isset($data['education_level']) && $data['education_level']=='PhD')?'selected':'' ?>>PhD</option>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Employment</label><select class="form-select" name="employment_status">
                            <option <?= (isset($data['employment_status']) && $data['employment_status']=='Employed')?'selected':'' ?>>Employed</option>
                            <option <?= (isset($data['employment_status']) && $data['employment_status']=='Self-Employed')?'selected':'' ?>>Self-Employed</option>
                            <option <?= (isset($data['employment_status']) && $data['employment_status']=='Unemployed')?'selected':'' ?>>Unemployed</option>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Loan Purpose</label><select class="form-select" name="loan_purpose">
                            <option <?= (isset($data['loan_purpose']) && $data['loan_purpose']=='Home')?'selected':'' ?>>Home</option>
                            <option <?= (isset($data['loan_purpose']) && $data['loan_purpose']=='Car')?'selected':'' ?>>Car</option>
                            <option <?= (isset($data['loan_purpose']) && $data['loan_purpose']=='Education')?'selected':'' ?>>Education</option>
                            <option <?= (isset($data['loan_purpose']) && $data['loan_purpose']=='Business')?'selected':'' ?>>Business</option>
                            <option <?= (isset($data['loan_purpose']) && $data['loan_purpose']=='Personal')?'selected':'' ?>>Personal</option>
                        </select></div>
                        <div class="col-md-6"><label class="form-label">Grade/Subgrade</label><select class="form-select" name="grade_subgrade">
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='A1')?'selected':'' ?>>A1</option>
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='A2')?'selected':'' ?>>A2</option>
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='B1')?'selected':'' ?>>B1</option>
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='B2')?'selected':'' ?>>B2</option>
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='C1')?'selected':'' ?>>C1</option>
                            <option <?= (isset($data['grade_subgrade']) && $data['grade_subgrade']=='C2')?'selected':'' ?>>C2</option>
                        </select></div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Predict Loan Approval</button>
                </form>
            </div>
        </div>

        <?php if($result): ?>
        <div class="card shadow-sm result-card text-center">
            <div class="card-body">
                <h5>Prediction Result</h5>
                <h6><?= htmlspecialchars($data["name"]) ?></h6>
                <div class="d-flex justify-content-around mt-3">
                    <div>
                        <div class="circle" style="background: conic-gradient(#198754 0deg <?= $result['approval_probability']*360 ?>deg, #e9ecef <?= $result['approval_probability']*360 ?>deg 360deg)">
                            <?= round($result['approval_probability']*100,2) ?>%
                        </div>
                        <p class="mt-2">Approval</p>
                    </div>
                    <div>
                        <div class="circle" style="background: conic-gradient(#dc3545 0deg <?= $result['rejection_probability']*360 ?>deg, #e9ecef <?= $result['rejection_probability']*360 ?>deg 360deg)">
                            <?= round($result['rejection_probability']*100,2) ?>%
                        </div>
                        <p class="mt-2">Rejection</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
