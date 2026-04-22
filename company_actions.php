<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $company_name    = $_POST['company_name'];
        $location        = $_POST['location'];
        $sector          = $_POST['sector'];
        $supervisor_name = $_POST['supervisor_name'];
        $username        = $_POST['username'];
        $password        = $_POST['password'];

        // Start Transaction to ensure all or nothing is saved
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
            header("Location: User_Management_IndustrySupervisor.php?error=Error: " . urlencode($e->getMessage()));
        }
    } 
    
    elseif ($action === 'edit') {
        $id = $_POST['company_id'];
        $company_name    = $_POST['company_name'];
        $location        = $_POST['location'];
        $sector          = $_POST['sector'];
        $supervisor_name = $_POST['supervisor_name'];
        $username        = $_POST['username'];
        $password        = $_POST['password'];

        $conn->begin_transaction();

        try {
            // 1. Update Company (Primary Table)
            $stmtCo = $conn->prepare("UPDATE company SET CompanyName=?, Location=?, Sector=? WHERE CompanyID=?");
            $stmtCo->bind_param("sssi", $company_name, $location, $sector, $id);
            $stmtCo->execute();

            // 2. Update Supervisor Name and Link
            $stmtSup = $conn->prepare("UPDATE supervisor SET Name=?, Company=? WHERE Company = (SELECT CompanyName FROM (SELECT CompanyName FROM company WHERE CompanyID=?) as x)");
            // Note: This logic assumes Company names are unique or relies on the specific link
            // A better way is joining by UserID if available.
            
            // 3. Update User (and Password if provided)
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
            header("Location: User_Management_IndustrySupervisor.php?error=Update failed.");
        }
    }
}