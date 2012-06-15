# DIY Video Sitemaps using The Viddler API
[https://github.com/viddler/Video-Sitemaps](https://github.com/viddler/Video-Sitemaps)

A simple way to create your own video sitemap files for your site.


## How to use


### PHP
  
1. [Download the latest version](https://github.com/viddler/Video-Sitemaps/downloads) of this package.
2. Place the scripts from the php folder on your server (the logs and sitemaps folder are optional)
3. Update the example file to include the path to the correct viddler wrapper
4. Update the example file with your Viddler API, username, password and path to save error logs (optional)
5. Update the example file method call `create` to the correct path to the folder to save your sitemaps
6. Run
7. Add all the files created to your robots.txt file (Sitemap: http://YOURSITE.com/sitemaps/video-sitemap.xml)

##### Example

    include '/path/to/your/viddler-api-wrapper';
    include 'video-sitemap.php';

    $sm = new Video_Sitemap('YOUR API KEY', 'YOUER VIDDLER USERNAME', 'YOUR VIDDLER PASSWORD', 'logs/');
    $sm->create('sitemaps/');
    $sm->stats();
  
##### Other Info

This version will create multiple sitemaps if need be. There is a max of 50,000 videos per sitemap file. The script will figure this out for you save the correct number of files while incrementing each file by 1.

Please see notes/comments in the video-sitemap.php for more info.

### Ruby

1. [https://github.com/viddler/video-sitemaps-ruby](https://github.com/viddler/video-sitemaps-ruby)

--------------

## Notes

Since this script, depending on your total public and domain restricted videos, can be quite resource intensive; it is recommended to only run it when  you need to and not on demand. IE: Once a day from a cronjob on your server.

## Support

- [viddler.com/help](http://viddler.com/help)

## Changelog

- **0.1** - June 11th, 2012
  - Initial commit.
  - PHP Helper created