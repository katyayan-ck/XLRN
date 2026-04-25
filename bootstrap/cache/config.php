<?php return array (
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\laragon\\www\\XLRN\\resources\\views',
    ),
    'compiled' => 'C:\\laragon\\www\\XLRN\\storage\\framework\\views',
  ),
  'app' => 
  array (
    'name' => 'Xceler8',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost/XLRN/public',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:Ao3yAK+H9OTaJgvJFZEXj3KN63/6zuWLsduP0SLr83A=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\KeywordValueServiceProvider',
      25 => 'App\\Providers\\SystemSettingServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'audit' => 
  array (
    'enabled' => true,
    'implementation' => 'OwenIt\\Auditing\\Models\\Audit',
    'user' => 
    array (
      'morph_prefix' => 'user',
      'guards' => 
      array (
        0 => 'web',
        1 => 'api',
      ),
      'resolver' => 'OwenIt\\Auditing\\Resolvers\\UserResolver',
    ),
    'resolvers' => 
    array (
      'ip_address' => 'OwenIt\\Auditing\\Resolvers\\IpAddressResolver',
      'user_agent' => 'OwenIt\\Auditing\\Resolvers\\UserAgentResolver',
      'url' => 'OwenIt\\Auditing\\Resolvers\\UrlResolver',
    ),
    'events' => 
    array (
      0 => 'created',
      1 => 'updated',
      2 => 'deleted',
      3 => 'restored',
    ),
    'strict' => false,
    'exclude' => 
    array (
    ),
    'empty_values' => true,
    'allowed_empty_values' => 
    array (
      0 => 'retrieved',
    ),
    'allowed_array_values' => false,
    'timestamps' => false,
    'threshold' => 0,
    'driver' => 'database',
    'drivers' => 
    array (
      'database' => 
      array (
        'table' => 'audits',
        'connection' => NULL,
      ),
    ),
    'queue' => 
    array (
      'enable' => false,
      'connection' => 'sync',
      'queue' => 'default',
      'delay' => 0,
    ),
    'console' => false,
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => NULL,
      ),
      'backpack' => 
      array (
        'driver' => 'session',
        'provider' => 'backpack',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
      'backpack' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
      'backpack' => 
      array (
        'provider' => 'backpack',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 600,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'backpack' => 
  array (
    'base' => 
    array (
      'registration_open' => true,
      'route_prefix' => 'admin',
      'web_middleware' => 'web',
      'setup_auth_routes' => true,
      'setup_dashboard_routes' => true,
      'setup_my_account_routes' => true,
      'setup_password_recovery_routes' => true,
      'setup_email_verification_routes' => false,
      'setup_email_verification_middleware' => true,
      'email_verification_throttle_access' => '3,15',
      'password_recovery_throttle_notifications' => 600,
      'password_recovery_token_expiration' => 60,
      'password_recovery_throttle_access' => '3,10',
      'user_model_fqn' => 'App\\Models\\User',
      'middleware_class' => 
      array (
        0 => 'App\\Http\\Middleware\\CheckIfAdmin',
        1 => 'Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull',
        2 => 'Backpack\\CRUD\\app\\Http\\Middleware\\AuthenticateSession',
      ),
      'middleware_key' => 'admin',
      'authentication_column' => 'email',
      'authentication_column_name' => 'Email',
      'email_column' => 'email',
      'guard' => 'backpack',
      'passwords' => 'backpack',
      'avatar_type' => 'gravatar',
      'gravatar_fallback' => 'blank',
      'root_disk_name' => 'root',
      'useDatabaseTransactions' => true,
      'token_username' => false,
    ),
    'basset' => 
    array (
      'dev_mode' => true,
      'verify_ssl_certificate' => true,
      'disk' => 'basset',
      'path' => 'basset',
      'cache_map' => true,
      'view_paths' => 
      array (
        0 => 'C:\\laragon\\www\\XLRN\\resources\\views',
      ),
      'asset_overrides' => NULL,
      'nonce' => NULL,
      'relative_paths' => true,
    ),
    'crud' => 
    array (
      'show_translatable_field_icon' => true,
      'translatable_field_icon_position' => 'right',
      'locales' => 
      array (
        'en' => 'English',
        'fr' => 'French',
        'it' => 'Italian',
        'ro' => 'Romanian',
      ),
      'view_namespaces' => 
      array (
        'buttons' => 
        array (
          0 => 'crud::buttons',
        ),
        'columns' => 
        array (
          0 => 'crud::columns',
        ),
        'fields' => 
        array (
          0 => 'crud::fields',
        ),
        'filters' => 
        array (
          0 => 'crud::filters',
        ),
      ),
      'uploaders' => 
      array (
        'withFiles' => 
        array (
          'image' => 'Backpack\\CRUD\\app\\Library\\Uploaders\\SingleBase64Image',
          'upload' => 'Backpack\\CRUD\\app\\Library\\Uploaders\\SingleFile',
          'upload_multiple' => 'Backpack\\CRUD\\app\\Library\\Uploaders\\MultipleFiles',
        ),
      ),
      'file_name_generator' => 'Backpack\\CRUD\\app\\Library\\Uploaders\\Support\\FileNameGenerator',
    ),
    'operations' => 
    array (
      'create' => 
      array (
        'contentClass' => 'col-md-12 bold-labels',
        'tabsType' => 'horizontal',
        'groupedErrors' => true,
        'inlineErrors' => true,
        'autoFocusOnFirstField' => true,
        'defaultSaveAction' => 'save_and_back',
        'showSaveActionChange' => true,
        'showCancelButton' => true,
        'warnBeforeLeaving' => false,
      ),
      'form' => 
      array (
        'contentClass' => 'col-md-12 bold-labels',
        'tabsType' => 'horizontal',
        'groupedErrors' => true,
        'inlineErrors' => true,
        'autoFocusOnFirstField' => true,
        'defaultSaveAction' => 'save_and_back',
        'showSaveActionChange' => false,
        'showCancelButton' => true,
        'warnBeforeLeaving' => false,
      ),
      'list' => 
      array (
        'contentClass' => 'col-md-12',
        'responsiveTable' => true,
        'persistentTable' => true,
        'searchableTable' => true,
        'searchDelay' => 400,
        'useFixedHeader' => true,
        'persistentTableDuration' => false,
        'defaultPageLength' => 10,
        'pageLengthMenu' => 
        array (
          0 => 
          array (
            0 => 10,
            1 => 25,
            2 => 50,
            3 => 100,
            4 => -1,
          ),
          1 => 
          array (
            0 => 10,
            1 => 25,
            2 => 50,
            3 => 100,
            4 => 'backpack::crud.all',
          ),
        ),
        'actionsColumnPriority' => 1,
        'lineButtonsAsDropdown' => false,
        'lineButtonsAsDropdownMinimum' => 1,
        'lineButtonsAsDropdownShowBefore' => 0,
        'resetButton' => true,
        'searchOperator' => 'like',
        'showEntryCount' => true,
        'eagerLoadRelationships' => true,
      ),
      'reorder' => 
      array (
        'contentClass' => 'col-md-12 col-md-offset-2',
        'escaped' => false,
      ),
      'show' => 
      array (
        'contentClass' => 'col-md-12',
        'component' => 'bp-datagrid',
        'setFromDb' => true,
        'timestamps' => true,
        'softDeletes' => false,
        'tabsEnabled' => false,
        'tabsType' => 'horizontal',
      ),
      'update' => 
      array (
        'contentClass' => 'col-md-12 bold-labels',
        'tabsType' => 'horizontal',
        'groupedErrors' => true,
        'inlineErrors' => true,
        'autoFocusOnFirstField' => true,
        'defaultSaveAction' => 'save_and_back',
        'showSaveActionChange' => true,
        'showCancelButton' => true,
        'showDeleteButton' => false,
        'warnBeforeLeaving' => false,
        'showTranslationNotice' => true,
        'eagerLoadRelationships' => false,
      ),
    ),
    'theme-tabler' => 
    array (
      'layout' => 'horizontal',
      'auth_layout' => 'default',
      'styles' => 
      array (
      ),
      'options' => 
      array (
        'defaultColorMode' => 'system',
        'useStickyHeader' => true,
        'useFluidContainers' => true,
        'sidebarFixed' => true,
        'doubleTopBarInHorizontalLayouts' => false,
        'showPasswordVisibilityToggler' => true,
      ),
      'classes' => 
      array (
        'body' => NULL,
        'topHeader' => NULL,
        'sidebar' => NULL,
        'menuHorizontalContainer' => NULL,
        'menuHorizontalContent' => NULL,
        'footer' => NULL,
        'table' => NULL,
        'tableWrapper' => NULL,
      ),
    ),
    'ui' => 
    array (
      'view_namespace' => 'backpack.theme-tabler::',
      'view_namespace_fallback' => 'backpack.theme-tabler::',
      'default_date_format' => 'DD/MM/YYYY',
      'default_datetime_format' => 'D MMM YYYY, HH:mm',
      'html_direction' => 'ltr',
      'project_name' => 'Xceler8 DMS',
      'meta_robots_content' => 'noindex, nofollow',
      'home_link' => '',
      'project_logo' => '<b>Back</b>pack',
      'breadcrumbs' => true,
      'developer_name' => 'BMPL',
      'developer_link' => 'http://BikanerMotors.com',
      'show_powered_by' => true,
      'show_getting_started' => true,
      'styles' => 
      array (
      ),
      'mix_styles' => 
      array (
      ),
      'vite_styles' => 
      array (
      ),
      'scripts' => 
      array (
      ),
      'mix_scripts' => 
      array (
      ),
      'vite_scripts' => 
      array (
      ),
      'classes' => 
      array (
        'table' => NULL,
        'tableWrapper' => NULL,
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'database',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'C:\\laragon\\www\\XLRN\\storage\\framework/cache/data',
        'lock_path' => 'C:\\laragon\\www\\XLRN\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'xceler8-cache-',
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'xlrn',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'xlrn',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'xlrn',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'xlrn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'xlrn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'xceler8-database-',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => 'C:\\laragon\\www\\XLRN\\storage\\framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\XLRN\\storage\\app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\XLRN\\storage\\app/public',
        'url' => 'http://localhost/XLRN/public/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
      'basset' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\XLRN\\storage\\app/public',
        'url' => 'http://localhost/XLRN/public/storage',
        'visibility' => 'public',
        'throw' => false,
      ),
      'root' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\XLRN',
      ),
    ),
    'links' => 
    array (
      'C:\\laragon\\www\\XLRN\\public\\storage' => 'C:\\laragon\\www\\XLRN\\storage\\app/public',
    ),
  ),
  'firebase' => 
  array (
    'enabled' => true,
    'project_id' => 'xceler8-a6a46',
    'credentials' => 
    array (
      'type' => 'service_account',
      'project_id' => 'xceler8-a6a46',
      'private_key_id' => '918c360657e9170d1e968786d0a74b28a4e741a4',
      'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCoCCmaxgZAv08H
4/fsVQGkPCtIaqudjodMNVoVTK1uMPMsyWjPHRxnsaa3Ho3dN2aQSXa44BbES7n6
cDzsJ0Jk9nhh1s64bwdCLsYLqvc1AhmxS+l0tR29jNgIjyVAoATTqAE68HPCENkg
yA5u+HJkKBinQAEETg+KNef23HT1eRbHk9heezou74hTv9fJHLCNbazJNYBXWWgc
L9Vracz3Hr0OxPwqFUwhR4Zn+l5YSx4kFFzb/3BfxhsJ1NCzYwzxfwkEULxiiFY6
ZX33f/8qaT6XLGwSpxf3jrll46w9s7tRSW2NiKkw2fan8SoaKBGIdMk+TzRGPZp9
q7JfsXwDAgMBAAECggEAB9HpbcSNgfHlUa6wWRVTsDsEoKgmOHzrmwZYnOkmoRwy
FQGCmTD3o3PHGHqZi5QKd+iTV+NF6/jCPP+ANlVVZ7F4xMTkdfzkz5WxrdVVD+h7
HZ80zDOpoKqvWyaeSzWnQ9prxDxwhrWWBUqKMWvqlvKuwwndgj/HxVzg4MI9xza6
IsE3GFJdL2akrC25xigitBpqJNBzTvuWaxS/YlxmSt1A2Aig0BnqjUfCeV6tl6fu
RynrhZl6b55iPd5KmCxQTD5NgXVUi6XIMvqb87nOGGpB3oO2dx3IatWiibmY/FBM
f54j9crTxDPfafBe/fgV8pifI9+VRXYQA9Ure7HT4QKBgQDVNPIdt8BfY4GHnHxS
Ok9qFkD+UUFilD+18nUKlUefVfgFe3WQq6YU+mzgif5WNbtTiswz5BjFipTF2g4S
rsyARdeLT1JVXnQgkVdewVtkf1PeVR3IB16Gv45NZX4i35KYcu3PIq1M9gkhQ/QV
NgWvtAUfowkTr8L8+m4fhEDFeQKBgQDJwgZ2iRABbJkjkEjlwax394AXc4oDSVH7
dy/s/Rjj/zIoG38GCjU+8PEpt50a6ITirhS8D9KFI1nNBhmhcPSEOva/By3oskyg
XHBqOIN3D8Z0K24TcHzQSR6w4apNnSPr+4NwbU8PF2z72S3acbmjk8JGsLNQV8B+
IUYGR0UaWwKBgB0joFeZboxa8DAVnhQq7gGkyvs3JcWQV2jJm1936ZMIT96H8hh0
rT0+wLSrh3xG2bRgSupoqU4OU6j61WOOSlrZsMzUaX9LanmtA5Dqwk/o6xB+95QI
Fc46zfsb/PJNNh7pzkC6D49uLO9D908S1BIge3bIdIwhQHgMzhI2pBeZAoGAP0tv
W6CENO4s3MtXEz0/LiOnO7Qzh/Rok8tAbci5Fk3pHkwB/ZMWQTi1b7D6yIZJqhOS
zazpDnuYoIlZYRxemV0mK6fE70uAXo6hdbFlDOUWDTvSvKZ9gZpu1m71ToQN0OqG
wa6JLuLafegUSoODCZ5BRIwKtRnSeb9WiIGzXCECgYBlstTv2vQYLARQXys1CU4O
aSmJalERWwUa2viZaXmgDP2O+CMGcfXtXr3GTx1uDdoxEpPPRmePkci3T8Lp6C/X
1+Pr0VBUy8YctsqnkCG5z9b21C+vZRqoIWlEEqQQoo3Opl5z8X0c3ZDkCb4U7sgA
kxbzEWfuXrXn6FWawjJ9og==
-----END PRIVATE KEY-----
',
      'client_email' => 'firebase-adminsdk-fbsvc@xceler8-a6a46.iam.gserviceaccount.com',
      'client_id' => '117557646410141511171',
      'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
      'token_uri' => 'https://oauth2.googleapis.com/token',
      'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
      'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40xceler8-a6a46.iam.gserviceaccount.com',
    ),
    'app' => 
    array (
      'name' => 'Xceler8',
      'package_name' => 'in.insigtech.android.xceler8',
      'ios_bundle_id' => 'com.insighttech.vdms',
    ),
    'timeout' => 30,
    'retry_attempts' => 3,
    'log_requests' => true,
  ),
  'gravatar' => 
  array (
    'default' => 
    array (
      'size' => 80,
      'fallback' => 'mp',
      'secure' => false,
      'maximumRating' => 'g',
      'forceDefault' => false,
      'forceExtension' => 'jpg',
    ),
  ),
  'l5-swagger' => 
  array (
    'default' => 'default',
    'documentations' => 
    array (
      'default' => 
      array (
        'api' => 
        array (
          'title' => 'Xceler8 API Documentation',
        ),
        'routes' => 
        array (
          'api' => 'api/v1/documentation',
        ),
        'paths' => 
        array (
          'use_absolute_path' => true,
          'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
          'docs_json' => 'api-docs.json',
          'docs_yaml' => 'api-docs.yaml',
          'format_to_use_for_docs' => 'json',
          'annotations' => 
          array (
            0 => 'C:\\laragon\\www\\XLRN\\app',
          ),
        ),
      ),
    ),
    'defaults' => 
    array (
      'routes' => 
      array (
        'docs' => 'docs',
        'oauth2_callback' => 'api/oauth2-callback',
        'middleware' => 
        array (
          'api' => 
          array (
          ),
          'asset' => 
          array (
          ),
          'docs' => 
          array (
          ),
          'oauth2_callback' => 
          array (
          ),
        ),
        'group_options' => 
        array (
        ),
      ),
      'paths' => 
      array (
        'docs' => 'C:\\laragon\\www\\XLRN\\storage\\api-docs',
        'views' => 'C:\\laragon\\www\\XLRN\\resources/views/vendor/l5-swagger',
        'base' => NULL,
        'excludes' => 
        array (
        ),
      ),
      'scanOptions' => 
      array (
        'default_processors_configuration' => 
        array (
        ),
        'analyser' => NULL,
        'analysis' => NULL,
        'processors' => 
        array (
        ),
        'pattern' => NULL,
        'exclude' => 
        array (
        ),
        'open_api_spec_version' => '3.0.0',
      ),
      'securityDefinitions' => 
      array (
        'securitySchemes' => 
        array (
        ),
        'security' => 
        array (
          0 => 
          array (
          ),
        ),
      ),
      'generate_always' => false,
      'generate_yaml_copy' => false,
      'proxy' => false,
      'additional_config_url' => NULL,
      'operations_sort' => NULL,
      'validator_url' => NULL,
      'ui' => 
      array (
        'display' => 
        array (
          'dark_mode' => false,
          'doc_expansion' => 'none',
          'filter' => true,
        ),
        'authorization' => 
        array (
          'persist_authorization' => false,
          'oauth2' => 
          array (
            'use_pkce_with_authorization_code_grant' => false,
          ),
        ),
      ),
      'constants' => 
      array (
        'L5_SWAGGER_CONST_HOST' => 'http://localhost/XLRN/public',
      ),
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\laragon\\www\\XLRN\\storage\\logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\laragon\\www\\XLRN\\storage\\logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => 'C:\\laragon\\www\\XLRN\\storage\\logs/laravel.log',
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => 'xcelr8.insightechindia.in',
        'port' => '465',
        'username' => 'no-reply@xcelr8.insightechindia.in',
        'password' => ')[6C[I6LMg1M61b7',
        'timeout' => NULL,
        'local_domain' => 'localhost',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'no-reply@xcelr8.insightechindia.in',
      'name' => 'Xceler8',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => 'C:\\laragon\\www\\XLRN\\resources\\views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'media-library' => 
  array (
    'disk_name' => 'public',
    'max_file_size' => 10485760,
    'queue_connection_name' => 'database',
    'queue_name' => '',
    'queue_conversions_by_default' => true,
    'queue_conversions_after_database_commit' => true,
    'media_model' => 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media',
    'media_observer' => 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Observers\\MediaObserver',
    'use_default_collection_serialization' => false,
    'temporary_upload_model' => 'Spatie\\MediaLibraryPro\\Models\\TemporaryUpload',
    'enable_temporary_uploads_session_affinity' => true,
    'generate_thumbnails_for_temporary_uploads' => true,
    'file_namer' => 'Spatie\\MediaLibrary\\Support\\FileNamer\\DefaultFileNamer',
    'path_generator' => 'Spatie\\MediaLibrary\\Support\\PathGenerator\\DefaultPathGenerator',
    'file_remover_class' => 'Spatie\\MediaLibrary\\Support\\FileRemover\\DefaultFileRemover',
    'custom_path_generators' => 
    array (
    ),
    'url_generator' => 'Spatie\\MediaLibrary\\Support\\UrlGenerator\\DefaultUrlGenerator',
    'moves_media_on_update' => false,
    'version_urls' => false,
    'image_optimizers' => 
    array (
      'Spatie\\ImageOptimizer\\Optimizers\\Jpegoptim' => 
      array (
        0 => '-m85',
        1 => '--force',
        2 => '--strip-all',
        3 => '--all-progressive',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Pngquant' => 
      array (
        0 => '--force',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Optipng' => 
      array (
        0 => '-i0',
        1 => '-o2',
        2 => '-quiet',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Svgo' => 
      array (
        0 => '--disable=cleanupIDs',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Gifsicle' => 
      array (
        0 => '-b',
        1 => '-O3',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Cwebp' => 
      array (
        0 => '-m 6',
        1 => '-pass 10',
        2 => '-mt',
        3 => '-q 90',
      ),
      'Spatie\\ImageOptimizer\\Optimizers\\Avifenc' => 
      array (
        0 => '-a cq-level=23',
        1 => '-j all',
        2 => '--min 0',
        3 => '--max 63',
        4 => '--minalpha 0',
        5 => '--maxalpha 63',
        6 => '-a end-usage=q',
        7 => '-a tune=ssim',
      ),
    ),
    'image_generators' => 
    array (
      0 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Image',
      1 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Webp',
      2 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Avif',
      3 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Pdf',
      4 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Svg',
      5 => 'Spatie\\MediaLibrary\\Conversions\\ImageGenerators\\Video',
    ),
    'temporary_directory_path' => NULL,
    'image_driver' => 'gd',
    'ffmpeg_path' => '/usr/bin/ffmpeg',
    'ffprobe_path' => '/usr/bin/ffprobe',
    'ffmpeg_timeout' => 900,
    'ffmpeg_threads' => 0,
    'jobs' => 
    array (
      'perform_conversions' => 'Spatie\\MediaLibrary\\Conversions\\Jobs\\PerformConversionsJob',
      'generate_responsive_images' => 'Spatie\\MediaLibrary\\ResponsiveImages\\Jobs\\GenerateResponsiveImagesJob',
    ),
    'media_downloader' => 'Spatie\\MediaLibrary\\Downloaders\\DefaultDownloader',
    'media_downloader_ssl' => true,
    'temporary_url_default_lifetime' => 5,
    'remote' => 
    array (
      'extra_headers' => 
      array (
        'CacheControl' => 'max-age=604800',
      ),
    ),
    'responsive_images' => 
    array (
      'width_calculator' => 'Spatie\\MediaLibrary\\ResponsiveImages\\WidthCalculator\\FileSizeOptimizedWidthCalculator',
      'use_tiny_placeholders' => true,
      'tiny_placeholder_generator' => 'Spatie\\MediaLibrary\\ResponsiveImages\\TinyPlaceholderGenerator\\Blurred',
    ),
    'enable_vapor_uploads' => false,
    'default_loading_attribute_value' => NULL,
    'prefix' => '',
    'force_lazy_loading' => true,
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'App\\Models\\IAM\\Permission',
      'role' => 'App\\Models\\IAM\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'xlr8_iam_roles',
      'permissions' => 'xlr8_iam_permissions',
      'model_has_permissions' => 'xlr8_iam_model_has_permissions',
      'model_has_roles' => 'xlr8_iam_model_has_roles',
      'role_has_permissions' => 'xlr8_iam_role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'prologue' => 
  array (
    'alerts' => 
    array (
      'levels' => 
      array (
        0 => 'info',
        1 => 'warning',
        2 => 'error',
        3 => 'success',
      ),
      'session_key' => 'alert_messages',
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
      'background' => 
      array (
        'driver' => 'background',
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'localhost',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'key' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => 'C:\\laragon\\www\\XLRN\\storage\\framework/sessions',
    'connection' => NULL,
    'table' => 'xlr8_system_sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'xceler8-session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
);
