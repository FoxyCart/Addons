<?php
$PluginInfo['GeSHiSyntaxHighlighter'] = array(
   'Name' => 'GeSHi Syntax Highlighter',
   'Description' => 'Provides GeSHi syntax highlighting for code that is placed within a &lt;pre&gt; &lt;/pre&gt; block in discussions. Use the "lang" attribute, e.g. &lt;pre lang="css"&gt; for CSS, lang="php" for PHP, etc. Check out http://qbnz.com/highlighter/ for more info on GeSHi and language options.',
   'Version' => '0.2',
   'Author' => "c.bavota",
   'AuthorEmail' => 'c@bavotasan.com',
   'AuthorUrl' => 'http://bavotasan.com/'
);

Gdn::FactoryInstall('CodeSyntaxFormatter', 'GeSHiSyntaxHighlighterPlugin', __FILE__, Gdn::FactorySingleton);

class GeSHiSyntaxHighlighterPlugin extends Gdn_Plugin {

	public function Base_Render_Before($Sender) {
		$Sender->AddCssFile($this->GetResource('styles.css', FALSE, FALSE));
		$geshi_syntax_jquery = '
<script type="text/javascript">
jQuery(document).ready(function(){
	var shrink = jQuery(".geshi_syntax").width();
	jQuery(".geshi_syntax").hover(function() {
		var expand = jQuery("table", this).width();
		if (expand > shrink) {
			jQuery(this)
				.stop(true, false)
				.css({
					zIndex: "100",
					position: "relative"
				})
				.animate({
					width: expand + "px"
				});
		}
	},
	function() {
		jQuery(this)
			.stop(true, false)
			.animate({
				width: shrink + "px"
			});
	});
});
</script>
		';
		$Sender->Head->AddString($geshi_syntax_jquery);
	}
	
	public function DiscussionController_BeforeCommentBody_Handler($Sender) {
		$Comment = $Sender->Discussion;
		$Comment->Body = ParseSyntax($Comment->Body);
		foreach($Sender->CommentData as $cdata) {
			$cdata->Body = ParseSyntax($cdata->Body);;
		}
	}   

	// AJAX posting of comments
	public function PostController_BeforeCommentBody_Handler($Sender) {
		$this->DiscussionController_BeforeCommentBody_Handler($Sender);
	}
	
	// AJAX preview of new discussions
	public function PostController_BeforeDiscussionRender_Handler($Sender) {
		if ($Sender->View == 'preview') {
		  $Sender->Comment->Body = ParseSyntax($Sender->Comment->Body);
		}
	}
	
	// AJAX preview of new comments
	public function PostController_BeforeCommentRender_Handler($Sender) {
		if ($Sender->View == 'preview') {
		  $Sender->Comment->Body = ParseSyntax($Sender->Comment->Body);
		}
	}
	
	// AJAX discussion edit preview
	public function PostController_BeforeDiscussionPreview_Handler($Sender) {
		$Sender->Comment->Body = ParseSyntax($Sender->Comment->Body);
	}

	public function Setup()  {
		// Nothing to do here!
	}

    public function FormatCode($String, $Lang) {
        return pre_entities(array("", $Lang, "", "", $String));
    }
}		

function ParseSyntax($String) {
	$String = preg_replace_callback("/\s*<pre(?:lang=[\"']([\w-]+)[\"']|line=[\"'](\d*)[\"']|escaped=[\"'](true|false)?[\"']|\s)+>(.*)<\/pre>\s*/siU",'pre_entities', $String);
	return $String;
}

function pre_entities($match) {
	include_once(PATH_PLUGINS.DS.'GeSHiSyntaxHighlighter'.DS.'geshi'.DS.'geshi.php');
	$language = strtolower(trim($match[1]));
    $line = trim($match[2]);
    $escaped = trim($match[3]);
    $code = geshisyntax_code_trim($match[4]);
    if ($escaped == "true") $code = htmlspecialchars_decode($code);
   
	$geshi = new GeSHi($code, $language);
    $geshi->enable_keyword_links(false);
	$geshi->enable_classes();	
	echo '<style type="text/css">'.$geshi->get_stylesheet()."</style>";

    $output = "<div class='geshi_syntax'><table><tr>";

    if ($line) {
        $output .= "<td class='line_numbers'>";
        $output .= geshisyntax_line_numbers($code, $line);
        $output .= "</td><td class='code'>";
        $output .= $geshi->parse_code();
        $output .= "</td>";
    } else {
		$output .= "<td>";
        $output .= "<div class='code'>";
        $output .= $geshi->parse_code();
        $output .= "</div>";
        $output .= "</td>";
	}
    return

    $output .= "</tr></table></div>";

    return $output;	
}

function geshisyntax_line_numbers($code, $start) {
    $line_count = count(explode("\n", $code));
    $output = "<pre>";
    for ($i = 0; $i < $line_count; $i++)
    {
        $output .= ($start + $i) . "\n";
    }
    $output .= "</pre>";
    return $output;
}

function geshisyntax_code_trim($code) {
    // special ltrim b/c leading whitespace matters on 1st line of content
    $code = preg_replace("/^\s*\n/siU", "", $code);
    $code = rtrim($code);
    return $code;
} 
