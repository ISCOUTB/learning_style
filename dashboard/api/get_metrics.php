<?php
require_once(dirname(__FILE__)."/../../lib.php");
if(isset($_POST)){
    $id_course = intval($_POST["id"]);
    get_metrics($id_course);
}
