<?php
/*
Copyright: © 2009 WebSharks, Inc. ( coded in the USA )
<mailto:support@websharks-inc.com> <http://www.websharks-inc.com/>

Released under the terms of the GNU General Public License.
You should have received a copy of the GNU General Public License,
along with this software. In the main directory, see: /licensing/
If not, see: <http://www.gnu.org/licenses/>.
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");
/*
Quick Cache Constants:
Do NOT edit this file directly, it is re-built dynamically.
Constants can be overwritten by defining them in /wp-config.php.
Otherwise, use the options panel inside WordPress® to configure everything.
*/
@define ("QUICK_CACHE_ENABLED", 1);
@define ("QUICK_CACHE_ENABLE_DEBUGGING", 0);
@define ("QUICK_CACHE_DONT_CACHE_WHEN_LOGGED_IN", 0);
@define ("QUICK_CACHE_DONT_CACHE_QUERY_STRING_REQUESTS", 0);
@define ("QUICK_CACHE_ALLOW_BROWSER_CACHE", 1);
@define ("QUICK_CACHE_EXPIRATION", 172800);
@define ("QUICK_CACHE_DONT_CACHE_THESE_URIS", "/wp\-app|wp\-signup|wp\-register|wp\-activate|wp\-login|wp\-admin|xmlrpc|channel|delete/");
@define ("QUICK_CACHE_DONT_CACHE_THESE_REFS", "");
@define ("QUICK_CACHE_DONT_CACHE_THESE_AGENTS", "/w3c_validator/i");
@define ("QUICK_CACHE_USE_FLOCK_OR_SEM", "sem");
@define ("QUICK_CACHE_VERSION_SALT", "");
/*
Function handles cache building / cache serving.
Registers the output handler: ws_plugin__qcache_builder()
*/
if (!function_exists ("ws_plugin__qcache_handler"))
	{
		function ws_plugin__qcache_handler () /* The Quick Cache handler. */
			{
				if (($cache_allowed = QUICK_CACHE_ENABLED) && !(is_multisite () && preg_match ("/\/files(?:\/|\?|$)/i", $_SERVER["REQUEST_URI"])))
					{
						define ("QUICK_CACHE_TIMER", microtime (true)); /* Start the timer. */
						/**/
						define ("QUICK_CACHE_DETECTED_ZLIB_OC", ((@ini_get ("zlib.output_compression") && preg_match ("/^(?:1|on|yes|true)$/i", ini_get ("zlib.output_compression"))) ? true : false));
						/**/
						define ("QUICK_CACHE_AUTO_CACHE_ENGINE", ($_SERVER["REMOTE_ADDR"] === $_SERVER["SERVER_ADDR"] && preg_match ("/Quick Cache \( Auto-Cache Engine \)/i", $_SERVER["HTTP_USER_AGENT"])));
						/**/
						if (QUICK_CACHE_AUTO_CACHE_ENGINE) /* Allows the Auto-Cache Engine to break the connection early to save time. */
							{
								@ignore_user_abort(true); /* Ignores user aborted requests, so the page can always finish loading. */
							}
						if (!QUICK_CACHE_ALLOW_BROWSER_CACHE && !$_GET["qcABC"])
							{
								header("Expires: " . gmdate ("D, d M Y H:i:s", strtotime ("-1 week")) . " GMT");
								header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
								header("Cache-Control: no-cache, must-revalidate, max-age=0");
								header("Pragma: no-cache");
							}
						if ((defined ("QUICK_CACHE_ALLOWED") && !QUICK_CACHE_ALLOWED) || (isset ($_SERVER["QUICK_CACHE_ALLOWED"]) && !$_SERVER["QUICK_CACHE_ALLOWED"]) || (isset ($_GET["qcAC"]) && !$_GET["qcAC"]) || defined ("DONOTCACHEPAGE"))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (preg_match ("/^CLI$/i", PHP_SAPI) && !QUICK_CACHE_AUTO_CACHE_ENGINE)
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if ($_SERVER["REMOTE_ADDR"] === $_SERVER["SERVER_ADDR"] && !QUICK_CACHE_AUTO_CACHE_ENGINE && stripos ($_SERVER["HTTP_HOST"], "localhost") === false && strpos ($_SERVER["HTTP_HOST"], "127.0.0.1") === false && (!defined ("LOCALHOST") || !LOCALHOST))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (preg_match ("/^(?:POST|PUT)$/i", $_SERVER["REQUEST_METHOD"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (is_admin () || preg_match ("/\/wp-admin(?:\/|\?|$)/", $_SERVER["REQUEST_URI"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (preg_match ("/\/(?:wp-app|wp-signup|wp-register|wp-activate|wp-login|xmlrpc)\.php/", $_SERVER["REQUEST_URI"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (defined ("SID") && SID) /* Possible Session. */
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (QUICK_CACHE_DONT_CACHE_QUERY_STRING_REQUESTS && strlen ($_SERVER["QUERY_STRING"]) && !$_GET["qcAC"] && !(count ($_GET) === 1 && isset ($_GET["qcABC"])))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (QUICK_CACHE_DONT_CACHE_THESE_AGENTS && !QUICK_CACHE_AUTO_CACHE_ENGINE && strlen ($_SERVER["HTTP_USER_AGENT"]) && preg_match (QUICK_CACHE_DONT_CACHE_THESE_AGENTS, $_SERVER["HTTP_USER_AGENT"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (QUICK_CACHE_DONT_CACHE_THESE_REFS && !QUICK_CACHE_AUTO_CACHE_ENGINE && strlen ($_SERVER["HTTP_REFERER"]) && preg_match (QUICK_CACHE_DONT_CACHE_THESE_REFS, $_SERVER["HTTP_REFERER"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (QUICK_CACHE_DONT_CACHE_THESE_URIS && preg_match (QUICK_CACHE_DONT_CACHE_THESE_URIS, $_SERVER["REQUEST_URI"]))
							{
								return; /* Return now. Nothing more to do here. */
							}
						else if (QUICK_CACHE_DONT_CACHE_WHEN_LOGGED_IN && is_array ($_COOKIE) && !empty ($_COOKIE) && is_array ($cookies = array ()))
							{
								$coma = "comment_author_"; /* This is hard-coded in. */
								$post = "wp-postpass"; /* Also hard-coded for protected posts. */
								$user = (defined ("USER_COOKIE")) ? USER_COOKIE : "wordpressuser_";
								$pass = (defined ("PASS_COOKIE")) ? PASS_COOKIE : "wordpresspass_";
								$auth = (defined ("AUTH_COOKIE")) ? AUTH_COOKIE : "wordpress_";
								$seca = (defined ("SECURE_AUTH_COOKIE")) ? SECURE_AUTH_COOKIE : "wordpress_sec_";
								$logd = (defined ("LOGGED_IN_COOKIE")) ? LOGGED_IN_COOKIE : "wordpress_logged_in_";
								$test = (defined ("TEST_COOKIE")) ? TEST_COOKIE : "wordpress_test_cookie";
								/**/
								$regx = "/^(?:" . preg_quote ($coma, "/") . "|" . preg_quote ($post, "/") . "|" . preg_quote ($user, "/") . "|" . preg_quote ($pass, "/") . "|" . preg_quote ($auth, "/") . "|" . preg_quote ($seca, "/") . "|" . preg_quote ($logd, "/") . "|" . preg_quote ($test, "/") . ")/";
								/**/
								foreach ($_COOKIE as $k => $v)
									if (preg_match ($regx, $k) && strlen ($v))
										$cookies[] = $k;
								/**/
								if (count ($cookies) > 0 && !(count ($cookies) === 1 && $cookies[0] === $test))
									return; /* Return now. Nothing more to do here. */
							}
						/**/
						list ($multisite_path) = preg_split ("/\//", trim ($_SERVER["REQUEST_URI"], "/"), 2);
						$multisite_path = (strlen ($multisite_path)) ? "/" . $multisite_path . "/" : "/";
						if (!is_multisite () || (defined ("SUBDOMAIN_INSTALL") && SUBDOMAIN_INSTALL))
							$multisite_path = "/"; /* Single slash in this case. */
						/**/
						$md5_1 = md5 (QUICK_CACHE_VERSION_SALT . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
						$md5_2 = md5 (preg_replace ("/\:[0-9]+$/", "", $_SERVER["HTTP_HOST"]) . $_SERVER["REQUEST_URI"]);
						$md5_3 = md5 (preg_replace ("/\:[0-9]+$/", "", $_SERVER["HTTP_HOST"]) . $multisite_path);
						/**/
						define ("QUICK_CACHE_FILE", WP_CONTENT_DIR . "/cache/qc-c-" . $md5_1 . "-" . $md5_2 . "-" . $md5_3);
						define ("QUICK_CACHE_FILE_DESC", QUICK_CACHE_VERSION_SALT . " " . $_SERVER["HTTP_HOST"] . htmlspecialchars ($_SERVER["REQUEST_URI"]));
						/**/
						if (file_exists (QUICK_CACHE_FILE) && filemtime (QUICK_CACHE_FILE) >= strtotime ("-" . QUICK_CACHE_EXPIRATION . " seconds"))
							{
								list ($headers, $cache) = explode ("<!--headers-->", file_get_contents (QUICK_CACHE_FILE), 2);
								/**/
								$headers_list = headers_list (); /* An array of headers already sent ( or ready to be sent ) by PHP routines. */
								foreach (unserialize ($headers) as $header) /* Preserves original headers sent with this file. */
									if (!in_array ($header, $headers_list)) /* Avoiding duplicate headers. */
										if (stripos ($header, "Last-Modified:") !== 0) /* NOT this one. */
											header($header);
								/**/
								$total_time = number_format (microtime (true) - QUICK_CACHE_TIMER, 5, ".", "");
								/**/
								$cache .= "\n<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->";
								$cache .= "\n<!-- Quick Cache Is Fully Functional :-) ... A Quick Cache file was just served for ( " . QUICK_CACHE_FILE_DESC . " ) in " . $total_time . " seconds, on " . date ("M jS, Y \a\\t g:i a T") . ". -->";
								/**/
								exit($cache); /* Exit with cache contents. */
							}
						else /* Else, we'll need to implement phase #2. Output buffering. */
							{
								function ws_plugin__qcache_gzdecode ($data = FALSE) /* See: http://us2.php.net/manual/en/function.readgzfile.php. */
									{
										if (function_exists ("zlib_get_coding_type")) /* Are ZLIB functions available on this site? */
											{
												if (!is_dir (WP_CONTENT_DIR . "/cache"))
													@mkdir (WP_CONTENT_DIR . "/cache", 0777, true);
												/**/
												if (strlen ($data) && is_dir (WP_CONTENT_DIR . "/cache") && is_writable (WP_CONTENT_DIR . "/cache"))
													{
														file_put_contents (($temp = tempnam (WP_CONTENT_DIR . "/cache", "qc-temp-")), $data);
														$data = gzread (($gz = gzopen ($temp, "rb")), 104857600); /* Up to 100 mbs. */
														gzclose($gz) . unlink ($temp);
														/**/
														return $data; /* Decoded. */
													}
											}
										/**/
										return false; /* False on failure. */
									}
								/**/
								function ws_plugin__qcache_builder ($buffer = FALSE)
									{
										if (defined ("QUICK_CACHE_ALLOWED") && !QUICK_CACHE_ALLOWED)
											return $buffer; /* Do NOT cache. */
										/**/
										else if (isset ($_SERVER["QUICK_CACHE_ALLOWED"]) && !$_SERVER["QUICK_CACHE_ALLOWED"])
											return $buffer; /* Do NOT cache. */
										/**/
										else if (isset ($_GET["qcAC"]) && !$_GET["qcAC"])
											return $buffer; /* Do NOT cache. */
										/**/
										else if (defined ("DONOTCACHEPAGE"))
											return $buffer; /* Do NOT cache. */
										/**/
										else if (QUICK_CACHE_DONT_CACHE_WHEN_LOGGED_IN && function_exists ("is_user_logged_in") && is_user_logged_in ())
											return $buffer; /* Do NOT cache. * Re-checking this here ( when possible ) because it's so important! */
										/*
										Now, we need to make sure NO theme and/or other plugins have used `ob_start("ob_gzhandler")`, or another form of gzip after the first phase of Quick Cache.
										In other words, we must NOT store a cache of already-compressed data. This often occurs when a theme attempts to use `ob_start("ob_gzhandler")` in `/header.php`.
										That should NOT be done in `/header.php`. If you must use `ob_start("ob_gzhandler")`, place it in: `/wp-config.php` so it's the top-level output buffer.
										*Note* If `zlib.output_compression` is enabled, we should EXPECT to see `zlib_get_coding_type()`. It's fine to use `zlib.output_compression`.
										* Using `zlib.output_compression` is preferred over `ob_start("ob_gzhandler")`, even when it's used inside `/wp-config.php`. */
										if (function_exists ("zlib_get_coding_type") && zlib_get_coding_type () === "gzip" && !QUICK_CACHE_DETECTED_ZLIB_OC)
											{
												if (!headers_sent () && ($_decoded = ws_plugin__qcache_gzdecode ($buffer))) /* @TODO: support deflate. */
													{
														$buffer = $_decoded; /* OK, now we'll use the decoded version. */
														header("Content-Encoding:"); /* And ditch this header. */
													}
												else /* If headers were already sent, it's too late. */
													return $buffer; /* Unable to cache this. */
											}
										/*
										Resume buffer scans. Buffer should be in an uncompressed format now. */
										if (!strlen ($buffer = trim ($buffer)))
											return $buffer; /* Do NOT cache. */
										/**/
										else if (strlen ($buffer) <= 2000 && preg_match ("/\<h1\>Error/i", $buffer))
											return $buffer; /* Do NOT cache. */
										/**/
										else if ($GLOBALS["QUICK_CACHE_STATUS"] && preg_match ("/^5/", $GLOBALS["QUICK_CACHE_STATUS"]))
											return $buffer; /* Do NOT cache. */
										/**/
										foreach (($headers = headers_list ()) as $i => $header) /* Go through all headers. */
											{
												if (preg_match ("/^Retry-After\:/i", $header) || preg_match ("/^Status\: 5/i", $header))
													return $buffer; /* Do NOT cache. */
												/**/
												else if (preg_match ("/^Content-Type\:/i", $header))
													$content_type = $header; /* The "last" one. */
											}
										/**/
										/* Disables caching when a PHP routine sets an incompatible Content-Type. */
										if ($content_type && !preg_match ("/xhtml|html|xml/i", $content_type))
											return $buffer; /* Do NOT cache. */
										/**/
										/* This is for the `Maintenance Mode` plugin. */
										/* <http://wordpress.org/extend/plugins/maintenance-mode/> */
										else if (function_exists ("is_maintenance") && is_maintenance ())
											return $buffer; /* Do NOT cache. */
										/**/
										/* This is for the `WP Maintenance Mode` plugin. */
										/* <http://wordpress.org/extend/plugins/wp-maintenance-mode/> */
										else if (function_exists ("did_action") && did_action ("wm_head"))
											return $buffer; /* Do NOT cache. */
										/**/
										if (!is_dir (WP_CONTENT_DIR . "/cache"))
											@mkdir (WP_CONTENT_DIR . "/cache", 0777, true);
										/**/
										if (is_dir (WP_CONTENT_DIR . "/cache") && is_writable (WP_CONTENT_DIR . "/cache"))
											{
												$total_time = number_format (microtime (true) - QUICK_CACHE_TIMER, 5, ".", "");
												/**/
												$cache = $buffer . "\n<!-- This Quick Cache file was built for ( " . QUICK_CACHE_FILE_DESC . " ) in " . $total_time . " seconds, on " . date ("M jS, Y \a\\t g:i a T") . ". -->";
												$cache .= "\n<!-- This Quick Cache file will automatically expire ( and be re-built automatically ) on " . date ("M jS, Y \a\\t g:i a T", strtotime ("+" . QUICK_CACHE_EXPIRATION . " seconds")) . " -->";
												/**/
												if (QUICK_CACHE_USE_FLOCK_OR_SEM === "sem" && function_exists ("sem_get") && ($mutex = @sem_get (1976, 1, 0644 | IPC_CREAT, 1)) && @sem_acquire ($mutex) && ($cached = true))
													file_put_contents (QUICK_CACHE_FILE, serialize ($headers) . "<!--headers-->" . $cache) . sem_release ($mutex);
												/**/
												else if (($mutex = @fopen (WP_CONTENT_DIR . "/cache/qc-l-mutex.lock", "w")) && @flock ($mutex, LOCK_EX) && ($cached = true))
													file_put_contents (QUICK_CACHE_FILE, serialize ($headers) . "<!--headers-->" . $cache) . flock ($mutex, LOCK_UN);
												/**/
												return ($cached) ? $cache : $buffer . "\n<!-- Quick Cache: failed to write cache, unable to obtain a mutex lock at the moment. Quick Cache will try again later. -->";
											}
										else /* We need to report that the cache/ directory is either non-existent ( and could not be created ) or it is not writable. */
											{
												return $buffer . "\n<!-- Quick Cache: failed to write cache. The cache/ directory is either non-existent ( and could not be created ) or it is not writable. -->";
											}
									}
								/**/
								ob_start("ws_plugin__qcache_builder"); /* Start output buffering. */
							}
					}
			}
	}
/**/
if (QUICK_CACHE_ENABLED) /* Only if enabled. */
	call_user_func("ws_plugin__qcache_handler");
?>