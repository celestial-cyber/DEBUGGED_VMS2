<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visitor Management System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background-color: #ffffff;
}

/* Center content card */
.center-box {
  border: 1px solid #ccc;
  border-radius: 12px;
  margin: 60px auto;
  max-width: 900px;
  padding: 80px 40px 40px 40px;
  text-align: center;
  position: relative;
  background: linear-gradient(to bottom, #d9f0d9, #ffffff);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Logos inside the card */
.logo-left {
  max-height: 150px;
  position: absolute;
  top: 20px;  
  left: 40px;
}

/* Illustration */
.illustration {
  max-height: 200px;
  margin: -60px auto -25px auto; 
  display: block;
}

/* Get Started Button */
/* Purple gradient button */
/* Purple gradient button that matches green background */
.btn-theme {
  background: linear-gradient(45deg, #6a0dad, #9b30ff); /* deep purple to violet */
  color: #fff;
  font-size: 16px;
  padding: 10px 20px;
  border-radius: 6px;
  text-decoration: none;
  border: none;
  transition: all 0.3s ease;
}

.btn-theme:hover {
  background: linear-gradient(45deg, #9b30ff, #6a0dad); /* reverse gradient on hover */
  color: #fff;
  transform: scale(1.05);
}

/* Visitor Management System title */
.main-title {
  font-family: 'Times New Roman', Times, serif;
  font-size: 45px;           
  font-weight: bold;
  color: #0d3d12;            
  text-align: left;
  margin-left: 150px;        
  margin-top: -25px;         
  margin-bottom: 20px;
}

/* L&D Initiative subtitle */
.subtitle {
  font-family: Arial, sans-serif;
  font-size: 16px;          
  font-weight: bold;
  color: #000000;           
  text-align: right;         
  margin-right: 130px;        
  margin-top: -15px;         
  margin-bottom: 20px;
}

footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #004d00;
    color: white;
    text-align: center;
    font-size: 14px;
    padding: 4px 0;
    z-index: 1000;
}
</style>
</head>
<body>

<!-- Main Center Card -->
<div class="center-box">
  <!-- Left Logo -->
  <img src="Images/SALogo.png" class="logo-left" alt="SA Logo">

  <!-- SPECANCIENS PRESENTS text -->
  <div style="display: flex; align-items: center; justify-content: flex-start; margin-bottom: 30px; margin-left: 150px; margin-top: -20px;">
    <h5 style="font-weight: bold; font-size: 18px; margin: 0;">SPECANCIENS PRESENTS</h5>
  </div>

  <!-- Main Title -->
  <h2 class="main-title">
    Visitor <span style="color:#145a20;">Management</span> System
  </h2>

  <!-- Subtitle -->
  <h5 class="subtitle">An L&D Initiative</h5>

  <!-- Illustration -->
  <img src="Images/buildings.png" alt="Illustration" class="illustration">

  <!-- Get Started Button -->
  <div class="mt-3">
    <!-- Redirect to landing.php -->
    <a href="landing.php" class="btn btn-theme">Click to Get Started</a>
  </div>
</div>

<footer>
    © 2025 SPECANCIENS - All Rights Reserved.
</footer>

</body>
</html>
