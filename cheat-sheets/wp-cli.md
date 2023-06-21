# WP-CLI - WordPress Command Line Interface
```wp --help```

## Search and Replace

### Search and Replace
```wp search-replace  'find-text' 'replacement-text'  --verbose --precise --all-tables --dry-run```


## Core Management (Updating)

### Update Core
```// Specify Version   
wp core download --force --skip-content --version=6.2   

// Simple Update Core   
wp core update   

// Update Plugins   
wp plugin update --all
```


## Plugin Management

### View Plugins
```wp plugin list```

### Search Repo For Plugin
```wp plugin search "Plain text search terms go here!"```

### Install/Delete plugins
```wp plugin install plugin_name second_plugin_name --activate   
wp plugin delete plugin_name`

### Activate/Deactivate Plugins
```wp plugin activate plugin_name   
wp plugin deactivate plugin_name```

### Update Plugins
```// Update all plugins   
wp plugin update --all   

// Update one plugin
wp plugin update plugin_name```



## User Management

### List Users
```wp user list```

### Create User with Admin Privileges
```wp user create new_username useremail@example.com --role=administrator --user_pass="password"```

### Modify User
```wp user update user_id --role=new_role   
wp user update user_id --user_email=newemail@example.com```

### Delete User
```wp user delete user_id --reassign=user_id_to_assign_content_to```


## WPDB / Database Queries
```wp db query "SELECT ID, user_login FROM wp_users;"```


## Conclusion
There are many easy to remember functions that make life a snap, especially when something goes wrong in GUI. There is a lot more that can be done, like clearing cache and managing themes, but I don't tend to use WP-CLI for that.
