<?php
session_start();
include '../includes/db.php';

if(array_key_exists("submit",$_POST)){
		$error = array();
		
		if(empty($_POST['account_number'])){
				$error['account_number'] = "Please Enter Account Number";
			}else{
					if(!is_numeric($_POST['account_number'])){
					$error['account_number'] = "Please Enter a Numeric Value";
				}
			}
		if(empty($error)){
				$statement = $conn->prepare("SELECT * FROM customer WHERE account_number=:ac");
				$statement->bindParam(":ac",$_POST['account_number']);
				$statement->execute();
				
				if($statement->rowCount() > 0){
						//if record exists
						$row = $statement->fetch(PDO::FETCH_BOTH);
						$_SESSION['id'] = $row['customer_id'];
						header("Location:dashboard.php");
						exit();
					}else{
							header("Location:login.php?error=Incorrect Account Number");
						}
			}
	}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bank Login</title>
</head>

<body>
	
    <h1>Swap Bank</h1>
    <hr />
	<form action="" method="post">
    
    <?php
    	if(isset($error['account_number'])){
				echo $error['account_number'];
			}	
	?>
    	<p>Account Number: <input type="text" name="account_number"/></p>
        <input type="submit" name="submit" value="Login" />
    </form>

</body>
</html>