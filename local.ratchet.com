server {
    listen   80;
    server_name  local.ratchet.com;

    access_log  /var/log/nginx/local.ratchet.com.access.log;
    error_log  /var/log/nginx/local.ratchet.com.error.log;
    
    root   /home/web/sites/local.ratchet.com/web;
    
    location / {
        try_files $uri $uri/ /index.php;
        index index.php;
    }

    # php5-fpm is running on port 9000
    location ~ \.php$ {
        include         /etc/nginx/fastcgi_params;
        fastcgi_pass    127.0.0.1:9000;
        fastcgi_index   index.php;
        fastcgi_param   SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param   APP_ENV prod;
        fastcgi_param   APP_DOMAIN $server_name;
    }
    
    # deny .files
    location ~ /\. {
        deny all;
    }
}
