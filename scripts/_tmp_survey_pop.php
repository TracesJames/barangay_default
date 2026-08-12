<?php
require 'c:/xampp/htdocs/barangay_default/connection.php';
require_once 'c:/xampp/htdocs/barangay_default/includes/nutrition_context.php';

$stmt=$con->query("SELECT barangay_id, survey_id, members_count FROM nutrition_household_survey ORDER BY barangay_id");
if(!$stmt){echo 'fail'; exit;}
$sum=0;
while($r=$stmt->fetch_assoc()){
  $barangayId=(string)($r['barangay_id']??'');
  $surveyId=(string)($r['survey_id']??'');
  $mc=(int)($r['members_count']??0);
  $stmt2=$con->prepare('SELECT COUNT(*) AS c FROM nutrition_household_family_member WHERE survey_id=? AND barangay_id=?');
  $stmt2->bind_param('ss',$surveyId,$barangayId);
  $stmt2->execute();
  $row2=$stmt2->get_result()->fetch_assoc();
  $c=(int)($row2['c']??0);
  $size=$mc;
  if($size<1){ $size=$c+1; }
  $size=max(1,(int)$size);
  $sum += (int)$size;
}

echo "surveyPopulation sum=".$sum."\n";
?>
