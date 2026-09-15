<?php
session_start();
include('includes/config.php');
require_once 'alerts.php';


ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


ob_start();

function login($email, $password) {
    global $conn;

    $password = md5($password);

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM tblemployees WHERE email_id = ? AND password = ?"
    );
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);

    $staffQuery = mysqli_stmt_get_result($stmt);

    if (!$staffQuery) {
        error_log("MySQL error: " . mysqli_error($conn));
        return array('status' => 'error', 'message' => 'Database error');
    }

    if (mysqli_num_rows($staffQuery) > 0) {
        $recordsRow = mysqli_fetch_assoc($staffQuery);
        return checkAndSetSession($recordsRow);
    }

    return array('status' => 'error', 'message' => 'Sai thông tin tài khoản hoặc mật khẩu');
}

function checkAndSetSession($userRecord) {
    if ($userRecord['lock_unlock'] == "true") {
        return array('status' => 'error', 'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
    }

    $_SESSION['slogin'] = $userRecord['emp_id'];
    $_SESSION['srole'] = $userRecord['role'];
    $_SESSION['semail'] = $userRecord['email_id'];
    $_SESSION['sstaff_id'] = $userRecord['staff_id'];
    $_SESSION['sfirstname'] = $userRecord['first_name'];
    $_SESSION['slastname'] = $userRecord['last_name'];
    $_SESSION['smiddlename'] = $userRecord['middle_name'];
    $_SESSION['scontact'] = $userRecord['phone_number'];
    $_SESSION['sdesignation'] = $userRecord['designation'];
    $_SESSION['is_supervisor'] = $userRecord['is_supervisor'];
    $_SESSION['simageurl'] = $userRecord['image_path'];
    $_SESSION['last_activity'] = time(); 
    $passwordReset = $userRecord['password_reset'];

    if ($userRecord['role'] == 'Admin') {
        $_SESSION['department'] = $userRecord['department'];
        $userType = 'admin';
    } elseif ($userRecord['role'] == 'Manager') {
        $_SESSION['department'] = $userRecord['department'];
        $userType = 'manager';
    } elseif ($userRecord['role'] == 'Staff') {
        $_SESSION['department'] = $userRecord['department'];
        $userType = 'staff';
    } else {
        return array('status' => 'error', 'message' => 'Không thể xác định vai trò người dùng');
    }

    return array(
        'status' => 'success',
        'message' => 'Đăng nhập thành công',
        'role' => $userType,
        'password_reset' => $passwordReset
    );
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
            $loginResponse = login($email, $password);

            ob_end_clean();

            header('Content-Type: application/json');
            echo json_encode($loginResponse);
        } catch (Exception $e) {
            ob_end_clean();
            error_log("Exception: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'error', 'message' => 'Lỗi xảy ra', 'error' => $e->getMessage()));
        }
        exit;
    }
}
?>
