<?php
   /*
   Plugin Name: NbConvert
   Description: A plugin to add ipynb files to a blog post or page using nbviewer
   Version: 1.0
   Author: Andrew Challis
   Author URI: http://www.andrewchallis.com
   License: MIT
   */

function md_github_handler($atts) {
  //run function that actually does the work of the plugin
  $md_output = md_github_function($atts);
  //send back text to replace shortcode in post
  return $md_output;
}

function nbconvert_get_most_recent_git_change_for_file_from_api($url) {

  $url_list = explode('/', $url);

  $owner = $url_list[3];
  $repo = $url_list[4];
  $branch = $url_list[6];
  $path = implode("/", array_slice($url_list, 7));

  $request_url = 'https://api.github.com/repos/'.$owner.'/'.$repo.'/commits/'.$branch.'?path='. $path.'&page=1';

  $context_params = array(
    'http' => array(
      'method' => 'GET',
      'user_agent' => 'Bogus user agent',
      'timeout' => 1
    )
  );


  $res = file_get_contents($request_url, FALSE, stream_context_create($context_params));

  $datetime = json_decode($res, true)['commit']['committer']['date'];

  $max_datetime = strtotime($datetime);
  $max_datetime_f = date('d/m/Y H:i:s', $max_datetime);

  return $max_datetime_f;
}

function md_github_function($atts) {
  //process plugin
  extract(shortcode_atts(array(
        'url' => "",
     ), $atts));

  $html = file_get_contents($url);
  $nb_output = md_github_getHTMLByTagName('article', $html);

  $last_update_date_time = nbconvert_get_most_recent_git_change_for_file_from_api($url);

  $pulled_md = '<div class="markdown-github">
    <div class="markdown-github-labels">
      <label class="github-link">
        <a href="'.$url.'" target="_blank">Check it out on github</a>
        <label class="github-last-update"> Last updated: '.$last_update_date_time.'</label>
      </label>
    </div>
    <article class="markdown-body>'.$nb_output.'
    </article>
  </div>';

  //send back text to calling function
  return $pulled_md;
}

function md_github_innerHTML(DOMNode $elm) {
  $innerHTML = '';
  $children  = $elm->childNodes;

  foreach($children as $child) {
    $innerHTML .= $elm->ownerDocument->saveHTML($child);
  }

  return $innerHTML;
}

function md_github_getHTMLByTagName($name, $html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    $node = $dom->getElementByTagName($name);
    if ($node) {
        $inner_output = md_github_innerHTML($node);
        return $inner_output;
    }
    return FALSE;
}

function md_github_enqueue_style() {
	wp_enqueue_style( 'NbConvert_md', plugins_url( '/css/github-markdown.css', __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'md_github_enqueue_style' );
add_shortcode("md_github", "md_github_handler");
