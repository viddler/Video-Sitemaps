<?
class Video_Sitemap extends Viddler_V2 {
  public $filename        = 'video-sitemap.xml';
  public $log_path        = NULL;
  public $max_pages       = 500;
  public $max_tags        = 32;
  public $page            = 1;
  public $sitemap_path    = NULL;
  public $stats           = array();
  
  protected $max_per      = 1;
  protected $per_page     = 1;
  protected $password     = NULL;
  protected $sessionid    = NULL;
  protected $total_per    = 0;
  protected $user         = NULL;
  protected $v            = NULL;

  /**
  Method: __construct
  Arguments: 
    $api_key  | string  | Your Viddler API Key
    $user     | string  | Your Viddler Username
    $password | string  | Your Viddler Password
    $log_path | string  | The path to store your log files, defaults to NULL for no logs
    
  Description: Creates object
  **/
  public function __construct($api_key, $user, $password, $log_path=NULL)
  {
    $this->v = parent::__construct($api_key);
    $this->log_path = (! empty($log_path)) ? $log_path : $this->log_path;
    $this->password = $password;
    $this->user     = $user;
  }
  
  /**
  Method: authenticate
  Arguments: N/A
  Description: Authenticates the current user
  **/
  protected function authenticate()
  {
    $auth = parent::viddler_users_auth(array(
      'user'      =>  $this->user,
      'password'  =>  $this->password
    ));
    
    if (! isset($auth['auth']['sessionid'])) {
      $this->log_error($auth);
      exit;
    }
    
    $this->sessionid = $auth['auth']['sessionid'];
  }
  
  /**
  Method: create
  Arguments: 
    $path       | string  | Defaults to NULL
    $fileaname  | string  | Defaults to video-sitemap.xml
    $page       | number  | Defaults to 1
    $max_tags   | number  | Defaults to 32
    
  Description: Starts the process to create your video sitemap(s)
  **/
  public function create($path=NULL, $filename='video-sitemap.xml', $page=1, $max_tags=32)
  {

    $this->sitemap_path = (! empty($path)) ? $path : $this->sitemap_path;
    $this->filename     = (! empty($filename)) ? $filename : $this->filename;
    $this->page         = (is_numeric($page)) ? $page : $this->page;
    $this->max_tags     = (is_numeric($max_tags)) ? $max_tags : $this->max_tags;

    //Get Videos
    $this->get_videos();
  }
  
  /**
  Method: get_videos
  Arguments: N/A
  Description:
    Loops thru all your videos and creates all the sitemaps. Each sitemap can only contain 50,000 videos.
    If you have more than 50,000 this class will create multiple sitemaps for you by simply incrementing
    the current sitemap number to your base video sitemap file.
    
    IE - base filename = video-sitemap.xml and you have 200,000 video. This method will create the following:
      - video-sitemap.xml
      - video-sitemap-1.xml
      - video-sitemap-2.xml
      - video-sitemap-3.xml
  **/
  protected function get_videos()
  {
    //Authenticate
    $this->authenticate();
    
    //Set some starting variables
    $current  = 0;
    $end      = false;
    $xml      = null;
    
    //Find the max pages
    $page = ($this->page == 1) ? 0 : $this->page;
    $max = floor(($this->max_per / $this->per_page) + $page);
    
    //Loop thru all videos to find every public and domain restricted video
    for ($this->page; $this->page <= $max; $this->page++) {
    
      //Get your videos, 100 at a time
      $videos = parent::viddler_videos_getByUser(array(
        'sessionid'   =>  $this->sessionid,
        'page'        =>  $this->page,
        'per_page'    =>  $this->per_page,
        'visibility'  =>  'public,embed',
      ));
  
      //Log any errors
      if (isset($videos['error'])) {
        $this->log_error($videos);
      }
  
      /**
      :: Start putting videos in xml variable
      :: Documentation: http://support.google.com/webmasters/bin/answer.py?hl=en&answer=80472
      **/
      $videos = (isset($videos['list_result']['video_list'])) ? $videos['list_result']['video_list'] : array();
      foreach ($videos as $k => $video) {
        $embed = (isset($video['permissions']['embed']) && $video['permissions']['embed'] != 'private') ? 'yes' : 'no';
        $download = (isset($video['permissions']['download']) && $video['permissions']['download'] != 'private') ? true : false;
      
        $xml .= '<url>';
        $xml .= '<loc>' . $video['permalink'] . '</loc>';
        $xml .= '<video:video>';
        $xml .= '<video:thumbnail_loc>http://www.viddler.com/thumbnail/' . $video['id'] . '</video:thumbnail_loc>';
        $xml .= '<video:title>' . htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8') . '</video:title>';
        $xml .= '<video:description>' . htmlspecialchars($video['description'], ENT_QUOTES, 'UTF-8') . '</video:description>';
        
        //If downloads for this content are not private, allow this param
        if ($download === true) {
          $xml .= '<video:content_loc>' . $video['files'][1]['url'] . '</video:content_loc>';
        }
        $xml .= '<video:player_loc allow_embed="' . $embed . '">http://www.viddler.com/embed/' . $video['id'] . '</video:player_loc>';
        $xml .= '<video:duration>' . $video['length'] . '</video:duration>';
        $xml .= '<video:view_count>' . $video['view_count'] . '</video:view_count>';
        $xml .= '<video:publication_date>' . date('Y-m-d', $video['upload_time']) . '</video:publication_date>';
        $xml .= '<video:family_friendly>yes</video:family_friendly>';
        $xml .= '<video:live>no</video:live>';
        
        //Get any global tags
        $total_tags = 0;
        if (isset($video['tags']) && count($video['tags']) > 0) {
          foreach ($video['tags'] as $tag) {
            if ($tag['type'] == 'global' && $total_tags != $max_tags) {
              $xml .= '<video:tag>' . htmlspecialchars($tag['text'], ENT_QUOTES, 'UTF-8') . '</video:tag>';
              $total_tags += 1;
            }
            elseif ($total_tags >= $max_tags) {
              break;
            }
          }
        }
        
        $xml .= '</video:video>';
        $xml .= '</url>';
        $current += 1;
        $this->stats['total_videos_indexed'] = (isset($this->stats['total_videos_indexed'])) ? $this->stats['total_videos_indexed'] + 1 : 1;
        
        if ($current >= $this->max_per) {
          break;
        }
      }
      
      //Figure if we should kill the loop (no more videos)
      if (count($videos) < $this->per_page || $current >= $this->max_per) {
        break;
      }
      
    }
  
    //Write the current sitemap file
    $this->write_sitemap($xml);
    
    //If broke out because we hit the 50,000 video max, start again at next interval and increment sitemap filename
    if ($current >= $this->max_per) { 
      $this->total_per += 1;
      $find = ($this->total_per > 1) ? '-' . ($this->total_per - 1) . '.xml' : '.xml';
      $current = 0;
      $this->create($this->sitemap_path, str_replace($find, '-' . $this->total_per . '.xml', $this->filename), $this->page + 1);
    }
  }
  
  /**
  Method: log_error
  Arguments: 
    $res  | array
    
  Description: Writes a log entry to your error log
  **/
  protected function log_error($res)
  {
    if (! empty($this->log_path)) {
      $str  = 'Date: ' . date('Y-m-d H:i:s') . "\n";
      $str .= print_r($res, TRUE);
      $str .= '----------------------------------' . "\n";
      @file_put_contents($this->log_path . 'video-sitemap-errors.log', $str);
    }
  }
  
  /**
  Method: stats
  Arguments: N/A
  Description: Does a simple print_r of the obj variable stats
  **/
  public function stats()
  {
    print '<pre>';
    print_r($this->stats);
    print '</pre>';
  }
  
  /**
  Method: write_sitemap
  Arguments: 
    $xml | string | Default to NULL
    
  Description: Actually writes the current xml doc to your server
  **/
  protected function write_sitemap($xml=NULL)
  {
    if (! empty($xml)) {
      $str = '<?xml version="1.0" encoding="UTF-8"?>';
      $str .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';
      $str .= $xml;
      $str .= '</urlset>';
      if (! file_put_contents($this->sitemap_path . $this->filename, $str)) {
        $this->log_error(array('Could not save: ' . $this->sitemap_path . $this->filename));
      }
      else {
        $this->stats['total_sitemaps_created'] = (isset($this->stats['total_sitemaps_created'])) ? $this->stats['total_sitemaps_created'] + 1 : 1;
      }
    }
  }

}