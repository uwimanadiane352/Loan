<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==============================
    // COLLECT & CAST FORM DATA
    // ==============================
    $data = [
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

    // ==============================
    // CALL FLASK API
    // ==============================
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
        die("Invalid response from ML API");
    }

    // ==============================
    // DATABASE CONNECTION
    // ==============================
    $conn = new mysqli("localhost", "root", "", "loan_system");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // ==============================
    // INSERT PREDICTION
    // ==============================
    $stmt = $conn->prepare("
        INSERT INTO predictions
        (annual_income, debt_to_income_ratio, credit_score, loan_amount, interest_rate,
         gender, marital_status, education_level, employment_status, loan_purpose,
         grade_subgrade, prediction, approval_probability, rejection_probability)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    /*
      TYPE STRING EXPLANATION
      d d i d d  -> numbers
      s s s s s s s -> strings
      d d -> probabilities
      TOTAL = 14
    */
    $stmt->bind_param(
        "ddiddsssssssdd",
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
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Prediction Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0">Loan Prediction Result</h3>
        </div>
        <div class="card-body">
            <div class="alert <?= ($result["prediction"] == "APPROVED") ? 'alert-success' : 'alert-danger' ?>">
                <strong>Decision:</strong> <?= $result["prediction"] ?><br>
                <strong>Approval Probability:</strong> <?= round($result["approval_probability"] * 100, 2) ?>%<br>
                <strong>Rejection Probability:</strong> <?= round($result["rejection_probability"] * 100, 2) ?>%
            </div>
            <a href="index.php" class="btn btn-primary">Back to Form</a>
        </div>
    </div>
</div>

</body>
</html>
