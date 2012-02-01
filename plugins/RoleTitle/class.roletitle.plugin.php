<?php if (!defined('APPLICATION')) exit();

// 0.2 - 2011-09-07 - mosullivan - Added InjectCssClass, Optimized querying.
// 0.3 - 2011-12-13 - linc - Add class to title span, make injected CSS class Vanilla-like (capitalized, no dashes).

$PluginInfo['RoleTitle'] = array(
   'Name' => 'RoleTitle',
   'Description' => "Adds user's roles under their name in comments and adds related css definitions to the comment containers.",
   'Version' => '0.3',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'MobileFriendly' => TRUE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class RoleTitlePlugin extends Gdn_Plugin {
    static $TitleDescriptions = array(
        'FoxyShop' => 'Developer of the FoxyShop WordPress plugin',
        'FoxyPress' => 'Developer of the FoxyPress WordPress plugin',
        'Consolibyte Quickbooks' => 'Developer of the Consolibyte Quickbooks plugin',
        'Integration Developer' => 'Our awesome FoxyCart developers who have made full integrations.'
    );
   /**
    * Inject the roles under the username on comments.
    */
   public function DiscussionController_CommentInfo_Handler($Sender) {
      $this->_AttachTitle($Sender);
   }
   public function PostController_CommentInfo_Handler($Sender) {
      $this->_AttachTitle($Sender);
   }
   private function _AttachTitle($Sender) {
      $Object = GetValue('Object', $Sender->EventArguments);
      $Roles = $Object ? GetValue('Roles', $Object, array()) : FALSE;
      if (!$Roles)
         return;

      $RolesTags = array();
      foreach ($Roles as $RawRole) {
         $CssClass = 'Role_'.str_replace(' ','_', Gdn_Format::AlphaNumeric($RawRole));
         $Title = self::$TitleDescriptions[$RawRole];
         $TitleAttr = ($Title ? " title='$Title'" : "");
         array_push($RolesTags, "<span class='{$CssClass}'{$TitleAttr}>$RawRole</span>");
      }

      echo '<span class="RoleTitle">'.implode($RolesTags, '').'</span> ';
   }

   /**
    * Inject css classes into the comment containers.
    */
   public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
      $this->_InjectCssClass($Sender);
   }
   public function PostController_BeforeCommentDisplay_Handler($Sender) {
      $this->_InjectCssClass($Sender);
   }
   private function _InjectCssClass($Sender) {
      $Object = GetValue('Object', $Sender->EventArguments);
      $CssRoles = $Object ? GetValue('Roles', $Object, array()) : FALSE;
      if (!$CssRoles)
         return;
      
      foreach ($CssRoles as &$RawRole)
         $RawRole = 'Role_'.str_replace(' ','_', Gdn_Format::AlphaNumeric($RawRole));
   
      if (count($CssRoles))
         $Sender->EventArguments['CssClass'] .= ' '.implode(' ',$CssRoles);
      
   }
   
   /**
    * Add the insert user's roles to the comment data so we can visually
    * identify different roles in the view.
    */ 
	public function DiscussionController_Render_Before($Sender) {
		$Session = Gdn::Session();
		if ($Session->IsValid()) {
			$JoinUser = array($Session->User);
			RoleModel::SetUserRoles($JoinUser, 'UserID');
		}
		if (property_exists($Sender, 'Discussion')) {
			$JoinDiscussion = array($Sender->Discussion);
			RoleModel::SetUserRoles($JoinDiscussion, 'InsertUserID');
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
		}
   }
   public function PostController_Render_Before($Sender) {
		if (property_exists($Sender, 'CommentData') && is_object($Sender->CommentData))
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
	}
   
}
