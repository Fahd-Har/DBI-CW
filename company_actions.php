<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- ACTION: ADD ---
    if ($action === 'add') {
        $company_name    = trim($_POST['company_name'] ?? '');
        $location        = trim($_POST['location'] ?? '');
        $sector          = trim($_POST['sector'] ?? '');
        $supervisor_name = trim($_POST['supervisor_name'] ?? '');
        $username        = trim($_POST['username'] ?? '');
        $password        = $_POST['password'] ?? '';

        // Pre-check: username must not already exist
        $userChk = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
        $userChk->bind_param("s", $username);
        $userChk->execute();
        if ($userChk->get_result()->num_rows > 0) {
            header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("Username '$username' is already taken"));
            exit;
        }

        $conn->begin_transaction();
        try {
            // 1. Create User Account
            $stmtUser = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'supervisor')");
            $stmtUser->bind_param("ss", $username, $password);
            $stmtUser->execute();
            $newUserId = $conn->insert_id;

             //  Create Company Profile
            $stmtCo = $conn->prepare("INSERT INTO company (CompanyName, Location, Sector) VALUES (?, ?, ?)");
            $stmtCo->bind_param("sss", $company_name, $location, $sector);
            $stmtCo->execute();
            $newCompanyId = $conn->insert_id;

            $stmtSup = $conn->prepare("INSERT INTO supervisor (UserID, Name, CompanyID) VALUES (?, ?, ?)");
            $stmtSup->bind_param("isi", $newUserId, $supervisor_name, $newCompanyId);
            $stmtSup->execute();

            $conn->commit();
            header("Location: User_Management_IndustrySupervisor.php?success=New company and supervisor added.");
            } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            if ($e->getCode() == 1062) {
                $msg = (strpos($e->getMessage(), 'uk_company_name') !== false)
                    ? "A company named '$company_name' already exists."
                    : "Username '$username' is already taken.";
            } else {
                $msg = "An unexpected error occurred. Please try again.";
            }
            header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode($msg));
        }
        exit;
    } 
    
    // --- ACTION: EDIT ---
    elseif ($action === 'edit') {
        $company_id      = intval($_POST['company_id'] ?? 0);
        $company_name    = trim($_POST['company_name'] ?? '');
        $location        = trim($_POST['location'] ?? '');
        $sector          = trim($_POST['sector'] ?? '');
        $supervisor_name = trim($_POST['supervisor_name'] ?? '');
        $username        = trim($_POST['username'] ?? '');
        $password        = $_POST['password'] ?? '';

        // Pre-check: find this supervisor's existing UserID, then ensure the new
        // username isn't already used by anyone.
        $curQ = $conn->prepare("SELECT UserID FROM supervisor WHERE CompanyID = ?");
        $curQ->bind_param("i", $company_id);
        $curQ->execute();
        $currentUserId = (int)($curQ->get_result()->fetch_assoc()['UserID'] ?? 0);
        if ($currentUserId === 0) {
            eader("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("This company has no supervisor on record. Add one via 'Add New Supervisor' instead."));
            exit;
        }

        $userChk = $conn->prepare("SELECT UserID FROM users WHERE Username = ? AND UserID <> ?");
        $userChk->bind_param("si", $username, $currentUserId);
        $userChk->execute();
        if ($userChk->get_result()->num_rows > 0) {
            header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("Username '$username' is already taken"));
            exit;
        }

        $conn->begin_transaction();
        try {
            
            //  Update Company
            $stmtCo = $conn->prepare("UPDATE company SET CompanyName=?, Location=?, Sector=? WHERE CompanyID=?");
            $stmtCo->bind_param("sssi", $company_name, $location, $sector, $company_id);
            $stmtCo->execute();

            $stmtSup = $conn->prepare("UPDATE supervisor SET Name=? WHERE CompanyID=?");
            $stmtSup->bind_param("si", $supervisor_name, $company_id);
            $stmtSup->execute();

            //  Update User account (look up via CompanyID)
            if (!empty($password)) {
                $stmtU = $conn->prepare("UPDATE users u JOIN supervisor s ON u.UserID = s.UserID SET u.Username=?, u.Password=? WHERE s.CompanyID=?");
                $stmtU->bind_param("ssi", $username, $password, $company_id);
            } else {
                $stmtU = $conn->prepare("UPDATE users u JOIN supervisor s ON u.UserID = s.UserID SET u.Username=? WHERE s.CompanyID=?");
                $stmtU->bind_param("si", $username, $company_id);
            }
            $stmtU->execute();

            $conn->commit();
            header("Location: User_Management_IndustrySupervisor.php?success=Record updated successfully.");
                } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            if ($e->getCode() == 1062) {
                $msg = (strpos($e->getMessage(), 'uk_company_name') !== false)
                    ? "A company named '$company_name' already exists."
                    : "Username '$username' is already taken.";
            } else {
                $msg = "An unexpected error occurred. Please try again.";
            }
            header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode($msg));
        }
        exit;
    }

    elseif ($action === 'delete') {
        $company_id = intval($_POST['company_id'] ?? 0);

        $conn->begin_transaction();
        try {
            $find = $conn->prepare("SELECT s.UserID FROM supervisor s WHERE s.CompanyID = ?");
            $find->bind_param("i", $company_id);
            $find->execute();
            $data = $find->get_result()->fetch_assoc();

            if ($data) {
                $uid = $data['UserID'];

                // Delete Supervisor (Child)
                $delSup = $conn->prepare("DELETE FROM supervisor WHERE UserID = ?");
                $delSup->bind_param("i", $uid);
                $delSup->execute();

                // Delete User (Parent of Supervisor)
                $delUser = $conn->prepare("DELETE FROM users WHERE UserID = ?");
                $delUser->bind_param("i", $uid);
                $delUser->execute();
            }

            // Delete Company (always — runs whether or not a supervisor existed)
            $delCo = $conn->prepare("DELETE FROM company WHERE CompanyID = ?");
            $delCo->bind_param("i", $company_id);
            $delCo->execute();

            $conn->commit();
            header("Location: User_Management_IndustrySupervisor.php?success=Supervisor and Company deleted.");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log("Delete Error: " . $e->getMessage());
            // Most common error is FK constraint with Internship table
            header("Location: User_Management_IndustrySupervisor.php?error=Cannot delete. This company is likely linked to an existing internship record.");
        }
        exit;
    }
}