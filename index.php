<?php
session_start();

// Redirect to login if not logged in
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $data = [
        "name" => $_POST["name"],
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
        CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (!$result || !isset($result["prediction"])) {
        echo json_encode(["error" => "Invalid response from ML API"]);
        exit;
    }

    // Save to DB
    $conn = new mysqli("localhost", "root", "", "loan_system");
    if ($conn->connect_error) { echo json_encode(["error"=>"DB error"]); exit; }

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
    $conn->close();

    echo json_encode($result);
    exit;
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
.result-card { display: none; margin-top: 20px; }

.circle {
    width: 150px; height: 150px; border-radius: 50%; display: inline-block; 
    background: conic-gradient(#198754 0deg, #198754 0deg, #e9ecef 0deg 360deg);
    line-height: 150px; text-align: center; font-weight: bold; font-size: 20px; color:#212529;
}
.circle-red { background: conic-gradient(#dc3545 0deg, #dc3545 0deg, #e9ecef 0deg 360deg); }
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
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="loanForm">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Applicant Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Annual Income</label>
                            <input type="number" class="form-control" name="annual_income" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Debt Ratio</label>
                            <input type="number" step="0.01" class="form-control" name="debt_to_income_ratio" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Credit Score</label>
                            <input type="number" class="form-control" name="credit_score" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loan Amount</label>
                            <input type="number" class="form-control" name="loan_amount" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Interest Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="interest_rate" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status">
                                <option>Single</option>
                                <option>Married</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Education</label>
                            <select class="form-select" name="education_level">
                                <option>Highschool</option>
                                <option>Bachelor</option>
                                <option>Master</option>
                                <option>PhD</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employment</label>
                            <select class="form-select" name="employment_status">
                                <option>Employed</option>
                                <option>Self-Employed</option>
                                <option>Unemployed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loan Purpose</label>
                            <select class="form-select" name="loan_purpose">
                                <option>Home</option>
                                <option>Car</option>
                                <option>Education</option>
                                <option>Business</option>
                                <option>Personal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grade/Subgrade</label>
                            <select class="form-select" name="grade_subgrade">
                                <option>A1</option>
                                <option>A2</option>
                                <option>B1</option>
                                <option>B2</option>
                                <option>C1</option>
                                <option>C2</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Predict Loan Approval</button>
                </form>
                <div id="spinner" class="text-center mt-3" style="display:none;">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Processing...</p>
                </div>
            </div>
        </div>

        <!-- Result Card -->
        <div class="card shadow-sm result-card mt-4" id="resultCard">
            <div class="card-body text-center">
                <h5>Prediction Result</h5>
                <h6 id="applicantName" class="mb-3"></h6>
                <div class="d-flex justify-content-around">
                    <div>
                        <div id="approvalCircle" class="circle"></div>
                        <p class="mt-2">Approval</p>
                    </div>
                    <div>
                        <div id="rejectionCircle" class="circle-red"></div>
                        <p class="mt-2">Rejection</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const form = document.getElementById("loanForm");
const spinner = document.getElementById("spinner");
const resultCard = document.getElementById("resultCard");

form.addEventListener("submit", async function(e){
    e.preventDefault();
    spinner.style.display="block";
    resultCard.style.display="none";

    const formData = new FormData(form);
    formData.append('ajax','1');
    let data={};
    formData.forEach((v,k)=>data[k]=v);

    const response = await fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams(data)
    });

    const result = await response.json();
    spinner.style.display="none";
    resultCard.style.display="block";

    document.getElementById("applicantName").innerText = data.name;

    const approvalPercent = (result.approval_probability*100).toFixed(2);
    const rejectionPercent = (result.rejection_probability*100).toFixed(2);

    document.getElementById("approvalCircle").style.background =
        `conic-gradient(#198754 0deg ${approvalPercent*3.6}deg, #e9ecef ${approvalPercent*3.6}deg 360deg)`;
    document.getElementById("approvalCircle").innerText = approvalPercent+"%";

    document.getElementById("rejectionCircle").style.background =
        `conic-gradient(#dc3545 0deg ${rejectionPercent*3.6}deg, #e9ecef ${rejectionPercent*3.6}deg 360deg)`;
    document.getElementById("rejectionCircle").innerText = rejectionPercent+"%";
});
</script>
</body>
</html>
