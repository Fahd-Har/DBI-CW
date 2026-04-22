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

        $conn->begin_transaction();
        try {
            // 1. Create User Account
            $stmtUser = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'supervisor')");
            $stmtUser->bind_param("ss", $username, $password);
            $stmtUser->execute();
            $newUserId = $conn->insert_id;

            // 2. Create Company Profile
            $stmtCo = $conn->prepare("INSERT INTO company (CompanyName, Location, Sector) VALUES (?, ?, ?)");
            $stmtCo->bind_param("sss", $company_name, $location, $sector);
            $stmtCo->execute();

            // 3. Create Supervisor Profile linked to the User
            $stmtSup = $conn->prepare("INSERT INTO supervisor (UserID, Name, Company) VALUES (?, ?, ?)");
            $stmtSup->bind_param("iss", $newUserId, $supervisor_name, $company_name);
            $stmtSup->execute();

            $conn->commit();
            header("Location: User_Management_IndustrySupervisor.php?success=New company and supervisor added.");
        } catch (Exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            header("Location: User_Management_IndustrySupervisor.php?error=Error: " . urlencode($e->getMessage()));
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

        $conn->begin_transaction();
        try {
            // 1. Get current company name before updating (needed to find the supervisor)
            $oldQ = $conn->prepare("SELECT CompanyName FROM company WHERE CompanyID = ?");
            $oldQ->bind_param("i", $company_id);
            $oldQ->execute();
            $oldName = $oldQ->get_result()->fetch_assoc()['CompanyName'] ?? '';

            // 2. Update Company
            $stmtCo = $conn->prepare("UPDATE company SET CompanyName=?, Location=?, Sector=? WHERE CompanyID=?");
            $stmtCo->bind_param("sssi", $company_name, $location, $sector, $company_id);
            $stmtCo->execute();

            // 3. Update Supervisor (linked by the old company name)
            $stmtSup = $conn->prepare("UPDATE supervisor SET Name=?, Company=? WHERE Company=?");
            $stmtSup->bind_param("sss", $supervisor_name, $company_name, $oldName);
            $stmtSup->execute();
            
            // 4. Update User Password if provided, otherwise just Username
            if (!empty($password)) {
                $stmtU = $conn->prepare("UPDATE users u JOIN supervisor s ON u.UserID = s.UserID SET u.Username=?, u.Password=? WHERE s.Company=?");
                $stmtU->bind_param("sss", $username, $password, $company_name);
            } else {
                $stmtU = $conn->prepare("UPDATE users u JOIN supervisor s ON u.UserID = s.UserID SET u.Username=? WHERE s.Company=?");
                $stmtU->bind_param("ss", $username, $company_name);
            }
            $stmtU->execute();

            $conn->commit();
            header("Location: User_Management_IndustrySupervisor.php?success=Record updated successfully.");
        } catch (Exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            header("Location: User_Management_IndustrySupervisor.php?error=Update failed.");
        }
        exit;
    }

    elseif ($action === 'delete') {
        $company_id = intval($_POST['company_id'] ?? 0);

        $conn->begin_transaction();
        try {
            // 1. Find the UserID and CompanyName
            $find = $conn->prepare("SELECT s.UserID, c.CompanyName FROM company c LEFT JOIN supervisor s ON s.Company = c.CompanyName WHERE c.CompanyID = ?");
            $find->bind_param("i", $company_id);
            $find->execute();
            $data = $find->get_result()->fetch_assoc();
            
            if ($data) {
                $uid = $data['UserID'];
                
                // 2. Delete Supervisor (Child)
                $delSup = $conn->prepare("DELETE FROM supervisor WHERE UserID = ?");
                $delSup->bind_param("i", $uid);
                $delSup->execute();

                // 3. Delete User (Parent of Supervisor)
                $delUser = $conn->prepare("DELETE FROM users WHERE UserID = ?");
                $delUser->bind_param("i", $uid);
                $delUser->execute();

                // 4. Delete Company
                $delCo = $conn->prepare("DELETE FROM company WHERE CompanyID = ?");
                $delCo->bind_param("i", $company_id);
                $delCo->execute();
            }

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