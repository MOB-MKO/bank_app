<?php
session_start();
include('../includes/user_auth.php');
include('../includes/db.php');
include('../includes/user_info.php');

if(isset($_POST['pay'])){
	$issue = array();
	
		if(empty($_POST['account_number'])){
				$issue['account_number'] = "Please Input Account Number";
			}elseif(!is_numeric($_POST['account_number'])){
					$issue['account_number'] = "Please Enter Numeric Value";
				}
				
		if(empty($_POST['amount'])){
				$issue['amount'] = "Please Specify Amount";
			}elseif(!is_numeric($_POST['account_number'])){
					$issue['amount'] = "Please Enter Numeric Value";
				}
				
		if(empty($issue)){
				if($_POST['amount'] > $current_users_data['account_balance']){
						header("Location:transfer.php?error=Insufficient Funds");
						die();
					}
				
				$fetch_beneficiary = $conn->prepare("SELECT * FROM customer WHERE account_number= :an");
				$fetch_beneficiary->bindParam(":an",$_POST['account_number']);
				$fetch_beneficiary->execute();
				
				if($fetch_beneficiary->rowCount() < 1){
						header("Location:transfer.php?error=Account Number Doesn't Exist");
						exit();
					}
					
				$beneficiary_record = $fetch_beneficiary->fetch(PDO::FETCH_BOTH);
					
				if($current_users_data['customer_id'] == $beneficiary_record['customer_id']){
						header("Location:transfer.php?error=You cannot send money to yourself");
						exit;
					}
					
				$sender_opening_balance = $current_users_data['account_balance'];
				$sender_closing_balance = $sender_opening_balance - $_POST['amount'];
				
				$debit = $conn->prepare("UPDATE customer SET account_balance=:ab WHERE account_number =:cua");
				$debit->bindParam(":ab",$sender_closing_balance);
				$debit->bindParam(":cua",$current_users_data['account_number']);
				$debit->execute();
				
				
				//Log a transaction
				$debit_transaction = $conn->prepare("INSERT INTO transactions VALUES(NULL,:sa,:ra,:ta,:pb,:fb,:tt,:cst,NOW(),NOW())");
				$data = array(
					":sa"=>$current_users_data['account_number'],
					":ra"=>$beneficiary_record['account_number'],
					":ta"=>$_POST['amount'],
					":pb"=>$sender_opening_balance,
					":fb"=>$sender_closing_balance,
					":tt"=>"debit",
					":cst"=>$current_users_data['customer_id']
				);
				
				$debit_transaction->execute($data);
				
				
				//Credit Transaction
				$beneficiary_opening_balance = $beneficiary_record['account_balance'];
				$beneficiary_closing_balance = $beneficiary_opening_balance + $_POST['amount'];
				
				$credit = $conn->prepare("UPDATE customer SET account_balance=:ab WHERE account_number =:ban");
				$credit->bindParam(":ab",$beneficiary_closing_balance);
				$credit->bindParam(":ban",$beneficiary_record['account_number']);
				$credit->execute();
					
					
				//Log a Transaction
				
				$credit_transaction = $conn->prepare("INSERT INTO transactions VALUES(NULL,:sa,:ra,:ta,:pb,:fb,:tt,:cst,NOW(),NOW())");
				$credit_data = array(
					":sa"=>$current_users_data['account_number'],
					":ra"=>$beneficiary_record['account_number'],
					":ta"=>$_POST['amount'],
					":pb"=>$beneficiary_opening_balance,
					":fb"=>$beneficiary_closing_balance,
					":tt"=>"credit",
					":cst"=>$beneficiary_record['customer_id']
				);
				$credit_transaction->execute($credit_data);
				
				header("Location:transfer.php?success=Transaction Successful");
					
			}
	}



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Transfer</title>
</head>

<body>
	<?php
	 include('../includes/user_header.php');
	 
	 if(isset($_GET['success'])){
		 	echo "<p style='color:green'>".$_GET['success']."</p>";
		 }
	 ?>
    
    <form action="" method="post">
    	<p>Account Number: <input type="text" name="account_number"  /></p>
        <p>Transaction Amount: <input type="text" name="amount" /></p>
        <input type="submit" name="pay" value="Transfer" />
    
    </form>

</body>
</html>