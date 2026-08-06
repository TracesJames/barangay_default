<?php

include_once '../connection.php';
include_once '../includes/auth_secretary.php';

try {
  
     
      
        $selected_values = $_REQUEST['selected_values'];

        if(!empty($selected_values)){

          $sql_person = "SELECT
          residence_information.residence_id,
          residence_information.first_name, 
          residence_information.middle_name,
          residence_information.last_name,
          residence_information.image,   
          residence_information.image_path
          FROM residence_information
          INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id  WHERE NOT residence_information.residence_id  IN  ($selected_values) AND archive ='NO'
         ORDER BY last_name ASC ";
            $query_person = $con->query($sql_person) or die ($con->error);
            while($row_person = $query_person->fetch_assoc()){
              if($row_person['middle_name'] != ''){
                $middle_name = $row_person['middle_name'][0].'.'.' '; 
              }else{
                $middle_name = $row_person['middle_name'].' '; 
              }
              
              ?>
                <option value="<?= $row_person['residence_id'] ?>" <?php 
                if($row_person['image_path'] != '' || $row_person['image_path'] != null || !empty($row_person['image_path'])){
                    echo 'data-image="'.$row_person['image_path'].'"';
                }else{
                  echo 'data-image="../assets/dist/img/blank_image.png"';
                }
               
              ?> >
              <?= $row_person['last_name'] .' '. $row_person['first_name'] .' '.  $middle_name  ?></option>
              <?php
            } 

      


        }else{

          $sql_person = "SELECT
          residence_information.residence_id,
          residence_information.first_name, 
          residence_information.middle_name,
          residence_information.last_name,
          residence_information.image,   
          residence_information.image_path
          FROM residence_information
          INNER JOIN residence_status ON residence_information.residence_id = residence_status.residence_id WHERE archive = 'NO'
         ORDER BY last_name ASC ";
            $query_person = $con->query($sql_person) or die ($con->error);
            while($row_person = $query_person->fetch_assoc()){
              if($row_person['middle_name'] != ''){
                $middle_name = $row_person['middle_name'][0].'.'.' '; 
              }else{
                $middle_name = $row_person['middle_name'].' '; 
              }

              ?>
                <option value="<?= $row_person['residence_id'] ?>" <?php 
                if($row_person['image_path'] != '' || $row_person['image_path'] != null || !empty($row_person['image_path'])){
                    echo 'data-image="'.$row_person['image_path'].'"';
                }else{
                  echo 'data-image="../assets/dist/img/blank_image.png"';
                }
               
              ?> >
              <?= $row_person['last_name'] .' '. $row_person['first_name'] .' '.  $middle_name  ?></option>
              <?php
            } 

        }
       
      

        

  
    
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
