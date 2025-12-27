<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "loan_system1");
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM predictions ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prediction History</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f1f4f9;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #212529, #343a40);
            color: white;
            padding-top: 20px;
        }

        .sidebar h5 {
            text-align: center;
            margin-bottom: 15px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dee2e6;
            text-decoration: none;
            padding: 10px 18px;
            margin: 4px 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #0d6efd;
            color: #fff;
        }

        /* Content */
        .content {
            padding: 20px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        /* Card */
        .card {
            border-radius: 12px;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            font-size: 13px;
            white-space: nowrap;
        }

        thead th {
            position: sticky;
            top: 0;
            background-color: #212529;
            color: white;
            z-index: 2;
        }

        td, th {
            vertical-align: middle;
        }

        /* Status Badges */
        .badge-approved {
            background-color: #198754;
            padding: 0.4em 0.7em;
            border-radius: 0.4rem;
            font-weight: 500;
        }

        .badge-rejected {
            background-color: #dc3545;
            padding: 0.4em 0.7em;
            border-radius: 0.4rem;
            font-weight: 500;
        }

        /* Row highlights */
        .row-approved {
            background-color: #d1e7dd !important; /* light green */
        }
        .row-rejected {
            background-color: #f8d7da !important; /* light red */
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <h5>Loan ML</h5>
            <hr class="text-secondary">

            <a href="index.php"><i class="bi bi-house"></i> Home</a>
            <a href="index.php"><i class="bi bi-graph-up"></i> New Prediction</a>
            <a href="history.php" class="active"><i class="bi bi-clock-history"></i> History</a>
            <a href="#"><i class="bi bi-gear"></i> Settings</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 content">
            <div class="page-title">Prediction History</div>

            <div class="card shadow-sm">
                <div class="card-body table-wrapper">

                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>NAME</th>
                                <th>Income (RWF)</th>
                                <th>Debt Ratio</th>
                                <th>Credit</th>
                                <th>Loan (RWF)</th>
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
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    // Determine row class
                                    $row_class = (strpos($row["prediction"], 'APPROVED') !== false) ? 'row-approved' : 'row-rejected';
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td><?= $row["id"] ?></td>
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

                                    <td>
                                        <?php 
                                            $pred = strtoupper(str_replace(['✅','❌'], '', $row["prediction"]));
                                            if (strpos($row["prediction"], 'APPROVED') !== false) {
                                                echo '<span class="badge badge-approved text-white">'.$pred.'</span>';
                                            } else {
                                                echo '<span class="badge badge-rejected text-white">'.$pred.'</span>';
                                            }
                                        ?>
                                    </td>

                                    <td><?= round($row["approval_probability"] * 100, 2) ?>%</td>
                                    <td><?= round($row["rejection_probability"] * 100, 2) ?>%</td>
                                    <td><?= date("Y-m-d H:i", strtotime($row["created_at"])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="17" class="text-center text-muted">
                                    No prediction records found
                                </td>
                            </tr>
                        <?php endif; ?>

                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
