<?php


class ContactNarrative {

  public function getUserNarrative ($userid) {
  
    $notes_query = mysql_query("select * from contact_narrative where AffectedUserID='".(int)$userid."' order by NoteTimestamp DESC");
	$notes_array = array();
	while ($note = mysql_fetch_assoc($notes_query)) {
	  $notes_array[] = $note;
	}
	return $notes_array;
  }
  
  public function saveUserNarrative($userid, $noting_user, $note_text) {
    mysql_query("insert into contact_narrative (NotingUserID, AffectedUserID, NoteText) values ('".$noting_user."', '".$userid."', '".addslashes($note_text)."')");
  }

}
?>