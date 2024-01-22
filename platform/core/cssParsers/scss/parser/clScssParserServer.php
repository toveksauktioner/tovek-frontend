<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2017 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://leafo.github.io/scssphp
 */

namespace Leafo\ScssPhp;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ServerException;
use Leafo\ScssPhp\Version;

/**
 * Server
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 */
class clScssParserServer
{
    /**
     * @var boolean
     */
    private $bShowErrorsAsCSS;

    /**
     * @var string
     */
    private $sDir;

    /**
     * @var string
     */
    private $sCacheDir;

    /**
     * @var \Leafo\ScssPhp\Compiler
     */
    public $oScss;

    /**
     * Join path components
     *
     * @param string $left  Path component, left of the directory separator
     * @param string $right Path component, right of the directory separator
     *
     * @return string
     */
    protected function join( $sLeft, $sRight ) {
        return rtrim($sLeft, '/\\') . DIRECTORY_SEPARATOR . ltrim($sRight, '/\\');
    }

    /**
	 * Override method so that we don't need to assign a GET-variable each time.
	 *
	 * @return string|null
	 */
	protected function inputName() {
		if( isset( $this->sDir ) && $this->sDir ) {
			return $this->sDir;
		}
		
		/**
		 * Fallback on regular method
		 * 
		 * Get name of requested .scss file
		 *
		 * @return string|null
		 */
		switch (true) {
            case isset($_GET['p']): return $_GET['p']; break;
            case isset($_SERVER['PATH_INFO']): return $_SERVER['PATH_INFO']; break;
            case isset($_SERVER['DOCUMENT_URI']): return substr($_SERVER['DOCUMENT_URI'], strlen($_SERVER['SCRIPT_NAME'])); break;
        }
	}

    /**
     * Get path to requested .scss file
     *
     * @return string
     */
    protected function findInput() {
        if( ($sInput = $this->inputName())            
            && strpos($sInput, '..') === false
            && substr($sInput, -5) === '.scss'
        ) {
            //$name = $this->join($this->sDir, $sInput);
            $sName = $sInput;
            
            if( is_file($sName) && is_readable($sName) ) {
                return $sName;
            }
        }
        
        return false;
    }

    /**
     * Get path to cached .css file
     *
     * @return string
     */
    protected function cacheName( $sFname ) {
        return $this->join( $this->sCacheDir, md5($sFname) . '.css' );
    }

    /**
     * Get path to meta data
     *
     * @return string
     */
    protected function metadataName( $sOut ) {
        return $sOut . '.meta';
    }

    /**
     * Determine whether .scss file needs to be re-compiled.
     *
     * @param string $sOut  Output path
     * @param string $etag ETag
     *
     * @return boolean True if compile required.
     */
    protected function needsCompile( $sOut, &$etag ) {        
		if( CSS_PARSER_CACHE === false ) {
			/**
			 * Force compile upon cache turned of
			 */
			return true;
		}
		
		if( !is_file($sOut) ) {
            return true;
        }

        $mtime = filemtime( $sOut );

        $metadataName = $this->metadataName( $sOut );

        if( is_readable($metadataName) ) {
            $metadata = unserialize( file_get_contents($metadataName) );

            foreach( $metadata['imports'] as $sImport => $originalMtime ) {
                $currentMtime = filemtime( $sImport );

                if( $currentMtime !== $originalMtime || $currentMtime > $mtime) {
                    return true;
                }
            }

            $metaVars = crc32( serialize($this->oScss->getVariables()) );

            if( $metaVars !== $metadata['vars'] ) {
                return true;
            }

            $etag = $metadata['etag'];

            return false;
        }

        return true;
    }

    /**
     * Get If-Modified-Since header from client request
     *
     * @return string|null
     */
    protected function getIfModifiedSinceHeader() {
        $sModifiedSince = null;

        if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) {
            $sModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'];

            if( false !== ($sSemicolonPos = strpos($sModifiedSince, ';')) ) {
                $sModifiedSince = substr( $sModifiedSince, 0, $sSemicolonPos );
            }
        }

        return $sModifiedSince;
    }

    /**
     * Get If-None-Match header from client request
     *
     * @return string|null
     */
    protected function getIfNoneMatchHeader() {
        $sNoneMatch = null;

        if( isset($_SERVER['HTTP_IF_NONE_MATCH']) ) {
            $sNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'];
        }

        return $sNoneMatch;
    }

    /**
     * Compile .scss file
     *
     * @param string $sIn  Input path (.scss)
     * @param string $sOut Output path (.css)
     *
     * @return array
     */
    protected function compile( $sIn, $sOut ) {
        $iStart   = microtime(true);
        $css     = $this->oScss->compile(file_get_contents($sIn), $sIn);
        $elapsed = round((microtime(true) - $iStart), 4);

        $v    = Version::VERSION;
        $t    = date('r');
        $css  = "/* compiled by scssphp $v on $t (${elapsed}s) */\n\n" . $css;
        $etag = md5($css);

        file_put_contents($sOut, $css);
        file_put_contents(
            $this->metadataName($sOut),
            serialize([
                'etag'    => $etag,
                'imports' => $this->oScss->getParsedFiles(),
                'vars'    => crc32(serialize($this->oScss->getVariables())),
            ])
        );

        return [$css, $etag];
    }

    /**
     * Format error as a pseudo-element in CSS
     *
     * @param \Exception $error
     *
     * @return string
     */
    protected function createErrorCSS( \Exception $error ) {
        $message = str_replace(
            ["'", "\n"],
            ["\\'", "\\A"],
            $error->getMessage() . ' in ' . $error->getFile() . ' on line ' . $error->getLine() . "\n"
        );
		
        return "body { display: none !important; }
                html:after {
                    background: white;
                    color: black;
                    content: '$message';
                    display: block !important;
                    font-family: mono;
                    padding: 1em;
                    white-space: pre;
                }";
    }

    /**
     * Render errors as a pseudo-element within valid CSS, displaying the errors on any
     * page that includes this CSS.
     *
     * @param boolean $show
     */
    public function showErrorsAsCSS( $bShow = true ) {
        $this->bShowErrorsAsCSS = $bShow;
    }

    /**
     * Compile .scss file
     *
     * @param string $sIn  Input file (.scss)
     * @param string $sOut Output file (.css) optional
     *
     * @return string|bool
     *
     * @throws \Leafo\ScssPhp\Exception\ServerException
     */
    public function compileFile( $sIn, $sOut = null ) {
        if( !is_readable($sIn) ) {
            throw new ServerException( 'load error: failed to find ' . $sIn );
        }

        $pi = pathinfo($sIn);

        $this->oScss->addImportPath( $pi['dirname'] . '/' );

        $sCompiled = $this->oScss->compile( file_get_contents($sIn), $sIn );

        if( $sOut !== null) {
            return file_put_contents( $sOut, $sCompiled );
        }

        return $sCompiled;
    }

    /**
     * Check if file need compiling
     *
     * @param string $sIn  Input file (.scss)
     * @param string $sOut Output file (.css)
     *
     * @return bool
     */
    public function checkedCompile( $sIn, $sOut ) {
        if( !is_file($sOut) || filemtime($sIn) > filemtime($sOut) ) {
            $this->compileFile( $sIn, $sOut );

            return true;
        }

        return false;
    }

    /**
     * Compile requested scss and serve css.  Outputs HTTP response.
     *
     * @param string $sSalt Prefix a string to the filename for creating the cache name hash
     */
    public function serve( $sSalt = '' ) {
        $sProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';

        if( $sInput = $this->findInput() ) {
            $sOutput = $this->cacheName( $sSalt . $sInput );
            $etag = $sNoneMatch = trim( $this->getIfNoneMatchHeader(), '"' );
			
            if( $this->needsCompile( $sOutput, $etag ) ) {
                try {
                    list($css, $etag) = $this->compile( $sInput, $sOutput );

                    $lastModified = gmdate('D, d M Y H:i:s', filemtime($sOutput)) . ' GMT';

                    header( 'Last-Modified: ' . $lastModified );
                    header( 'Content-type: text/css' );
                    header( 'ETag: "' . $etag . '"' );

                    echo $css;
                } catch( \Throwable $oException ) {
					if( !$this->bShowErrorsAsCSS ) {
						return;
						
					} elseif( $this->bShowErrorsAsCSS ) {
                        header('Content-type: text/css');

                        echo $this->createErrorCSS( $oException );
                    } else {
                        header( $sProtocol . ' 500 Internal Server Error' );
                        header( 'Content-type: text/plain' );

                        echo 'Parse error: ' . $oException->getMessage() . "\n";
                    }
                } catch( \Exception $oException ) {
                    if( !$this->bShowErrorsAsCSS ) {
						return;
						
					} elseif( $this->bShowErrorsAsCSS ) {
                        header('Content-type: text/css');

                        echo $this->createErrorCSS( $oException );
                    } else {
                        header( $sProtocol . ' 500 Internal Server Error' );
                        header( 'Content-type: text/plain' );

                        echo 'Parse error: ' . $oException->getMessage() . "\n";
                    }
                }

                return;
            }

            header( 'X-SCSS-Cache: true' );
            header( 'Content-type: text/css' );
            header( 'ETag: "' . $etag . '"' );

            if( $etag === $sNoneMatch ) {
                header($sProtocol . ' 304 Not Modified');

                return;
            }

            $sModifiedSince = $this->getIfModifiedSinceHeader();
            $mtime = filemtime( $sOutput );

            if( strtotime($sModifiedSince) === $mtime ) {
                header( $sProtocol . ' 304 Not Modified' );
                return;
            }

            $lastModified  = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
            header( 'Last-Modified: ' . $lastModified );

            echo file_get_contents( $sOutput );
            return;
        }

        header( $sProtocol . ' 404 Not Found' );
        header( 'Content-type: text/plain' );

        $v = Version::VERSION;
        echo "/* INPUT NOT FOUND scss $v */\n";
    }

    /**
     * Based on explicit input/output files does a full change check on cache before compiling.
     *
     * @param string  $sIn
     * @param string  $sOut
     * @param boolean $force
     *
     * @return string Compiled CSS results
     *
     * @throws \Leafo\ScssPhp\Exception\ServerException
     */
    public function checkedCachedCompile( $sIn, $sOut, $force = false ) {
        if( !is_file($sIn) || !is_readable($sIn) ) {
            throw new ServerException('Invalid or unreadable input file specified.');
        }

        if( is_dir($sOut) || !is_writable(file_exists($sOut) ? $sOut : dirname($sOut)) ) {
            throw new ServerException('Invalid or unwritable output file specified.');
        }

        if( $force || $this->needsCompile($sOut, $etag) ) {
            list($css, $etag) = $this->compile( $sIn, $sOut );
        } else {
            $css = file_get_contents( $sOut );
        }

        return $css;
    }

    /**
     * Execute scssphp on a .scss file or a scssphp cache structure
     *
     * The scssphp cache structure contains information about a specific
     * scss file having been parsed. It can be used as a hint for future
     * calls to determine whether or not a rebuild is required.
     *
     * The cache structure contains two important keys that may be used
     * externally:
     *
     * compiled: The final compiled CSS
     * updated: The time (in seconds) the CSS was last compiled
     *
     * The cache structure is a plain-ol' PHP associative array and can
     * be serialized and unserialized without a hitch.
     *
     * @param mixed   $sIn    Input
     * @param boolean $force Force rebuild?
     *
     * @return array scssphp cache structure
     */
    public function cachedCompile( $sIn, $force = false ) {
        // assume no root
        $root = null;

        if( is_string($sIn) ) {
            $root = $sIn;
			
        } elseif( is_array($sIn) and isset($sIn['root']) ) {
            if( $force or !isset($sIn['files']) ) {
                // If we are forcing a recompile or if for some reason the
                // structure does not contain any file information we should
                // specify the root to trigger a rebuild.
                $root = $sIn['root'];
				
            } elseif( isset($sIn['files']) and is_array($sIn['files']) ) {
                foreach( $sIn['files'] as $fname => $ftime) {
                    if( !file_exists($fname) or filemtime($fname) > $ftime) {
                        // One of the files we knew about previously has changed
                        // so we should look at our incoming root again.
                        $root = $sIn['root'];
                        break;
                    }
                }
            }
        } else {
            // TODO: Throw an exception? We got neither a string nor something
            // that looks like a compatible lessphp cache structure.
            return null;
        }

        if( $root !== null) {
            // If we have a root value which means we should rebuild.
            $sOut = array();
            $sOut['root'] = $root;
            $sOut['compiled'] = $this->compileFile($root);
            $sOut['files'] = $this->oScss->getParsedFiles();
            $sOut['updated'] = time();
            return $sOut;
        } else {
            // No changes, pass back the structure
            // we were given initially.
            return $sIn;
        }
    }

    /**
     * Constructor
     *
     * @param string                       $dir      Root directory to .scss files
     * @param string                       $cacheDir Cache directory
     * @param \Leafo\ScssPhp\Compiler|null $scss     SCSS compiler instance
     */
    public function __construct( $dir, $cacheDir = null, $scss = null ) {
        $this->sDir = $dir;

        if( !isset($cacheDir) ) {
            $cacheDir = $this->join($dir, 'scss_cache');
        }

        $this->sCacheDir = $cacheDir;

        if( !is_dir($this->sCacheDir) ) {
            throw new ServerException('Cache directory doesn\'t exist: ' . $cacheDir);
        }

        if( !isset($scss) ) {
            $scss = new Compiler();
            $scss->setImportPaths($this->sDir);
        }

        $this->oScss = $scss;
        $this->bShowErrorsAsCSS = false;

        if( !ini_get('date.timezone') ) {
            throw new ServerException('Default date.timezone not set');
        }
    }
	
}