<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSAT</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link href="public/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
    <script src="public/js/app.js"></script>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include 'src/views/sidebar.php'; ?>
        <div id="page-content-wrapper" class="w-100">
            <?php include 'src/views/header.php'; ?>
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'main';
            if ($page == 'main') {
                include 'src/views/main.php';
            } elseif ($page == 'tipos') {
                include 'src/views/tipo.php';
            } else {
                include 'src/views/main.php'; // Default page
            }
            ?>
        </div>
    </div>
    <?php include 'src/views/modal.php'; ?>
    <?php include 'src/views/modal_tipos.php'; ?>
</body>

</html>