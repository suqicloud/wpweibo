<?php
/*
 * Plugin Name: 小半微心情
 * Plugin URI: https://www.jingxialai.com/4307.html
 * Description: 心情动态说说前台用户版，支持所有用户发布心情，点赞，评论，白名单等常规设置。
 * Version: 3.0.2
 * Author: Summer
 * License: GPL License
 * Author URI: https://www.jingxialai.com/
*/

// 激活插件创建数据表
function ws_weibo_activate() {
    global $wpdb;
    
    // 表名
    $feeling_table = $wpdb->prefix . 'ws_weibo_feelings';
    $comments_table = $wpdb->prefix . 'ws_weibo_comments';
    
    // 设置字符集和排序规则
    $charset_collate = $wpdb->get_charset_collate();
    
    // 创建ws_weibo_feelings表 微博
    $sql1 = "CREATE TABLE $feeling_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        content text NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        likes_count int(11) NOT NULL DEFAULT 0,
        liked_by text,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // 创建ws_weibo_comments表 评论
    $sql2 = "CREATE TABLE $comments_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        feeling_id mediumint(9) NOT NULL,
        user_id mediumint(9) NOT NULL,
        content text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // 更新数据表
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // 执行创建表操作
    dbDelta($sql1);
    dbDelta($sql2);
}

// 激活插件时调用该函数
register_activation_hook(__FILE__, 'ws_weibo_activate');


require_once('wsweibo-comments.php'); //评论文件
require_once('wsweibo-list.php'); //微博列表
require_once('wsweibo-user.php'); //用户封禁、违规词、白名单
require_once('wsweibo-api.php'); // APIKey接口

// 获取IP地址
function get_real_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // 可能存在多个IP，取第一个作为真实客户端IP
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// 插件列表页面添加设置入口
function ws_weibo_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=ws_weibo_weibo_settings">设置</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ws_weibo_add_settings_link');


// 插件激活时创建前台页面
function ws_weibo_create_feeling_page_on_activation() {
    // 查询是否已有包含短代码 [ws_weibo_feeling] 的页面
    $query = new WP_Query(array(
        'post_type'  => 'page', // 查询页面
        's'          => '[ws_weibo_feeling]', // 搜索包含短代码的页面
        'post_status' => 'publish', // 只查询已发布的页面
    ));

    // 如果没有找到包含短代码的页面，则创建新页面
    if (!$query->have_posts()) {
        // 创建页面数据
        $page_data = array(
            'post_title'   => '微心情动态', // 页面标题
            'post_content' => '[ws_weibo_feeling]', // 插入短代码
            'post_status'  => 'publish', // 发布状态
            'post_type'    => 'page', // 页面
        );

        // 插入页面
        wp_insert_post($page_data);
    }

    // 重置查询
    wp_reset_postdata();
}
register_activation_hook(__FILE__, 'ws_weibo_create_feeling_page_on_activation');

// 加载CSS文件，仅在短代码页面加载
function ws_weibo_enqueue_styles() {
    $post = get_post();  // 获取当前页面对象

    // 确保有有效的页面对象且页面内容包含短代码
    if ($post && has_shortcode($post->post_content, 'ws_weibo_feeling')) {
        wp_enqueue_style('ws-feelings-style', plugins_url('styles.css', __FILE__));
        echo '<style>.banner-page{display:none;}</style>';  //在modown主题调试的，只在这个页面取消主题的页面标题板块样式
    }
}
add_action('wp_enqueue_scripts', 'ws_weibo_enqueue_styles');

// 添加菜单页面
function ws_weibo_admin_menu() {
    add_menu_page('微心情动态说说', '微博管理', 'manage_options', 'ws_weibo_feelings_manage', 'ws_weibo_manage_feelings_page', 'dashicons-admin-site', 26);
    add_submenu_page('ws_weibo_feelings_manage', '微博列表管理', '微博列表', 'manage_options', 'ws_weibo_feelings_manage', 'ws_weibo_manage_feelings_page');
    add_submenu_page('ws_weibo_feelings_manage', '微博评论管理', '评论列表', 'manage_options', 'ws_weibo_comments_manage', 'ws_weibo_manage_comments_page');
    add_submenu_page('ws_weibo_feelings_manage', '封禁用户设置', '封禁用户', 'manage_options', 'ws_weibo_user_settings', 'ws_weibo_user_settings_page');
    add_submenu_page('ws_weibo_feelings_manage', '关键词屏蔽设置', '关键词屏蔽', 'manage_options', 'ws_weibo_keyword_block_manage', 'ws_weibo_keyword_block_manage_page');
    add_submenu_page('ws_weibo_feelings_manage', '网址白名单设置', '网址白名单', 'manage_options', 'ws_weibo_whitelist_manage', 'ws_weibo_whitelist_manage_page');
    add_submenu_page('ws_weibo_feelings_manage', '微博常规设置', '微博设置', 'manage_options', 'ws_weibo_weibo_settings', 'ws_weibo_weibo_settings_page');
    add_submenu_page('ws_weibo_feelings_manage', '微博API设置', 'API设置', 'manage_options', 'ws_weibo_api_settings', 'ws_weibo_api_settings_page'); 
}
add_action('admin_menu', 'ws_weibo_admin_menu');


// 微博发布时间限制常量
define('ws_weibo_WEIBO_POST_TIME_OPTION', 'ws_weibo_weibo_post_time');
// 隐藏用户统计常量
define('ws_weibo_HIDE_USER_STATISTICS_OPTION', 'ws_weibo_hide_user_statistics');
// 关闭评论常量
define('ws_weibo_CLOSE_COMMENTS_OPTION', 'ws_weibo_close_comments');

// 微博设置页面
function ws_weibo_weibo_settings_page() {
    // 处理表单提交
    if (isset($_POST['submit_weibo_settings'])) {
        //获取数据处理
        // 发布时间        
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $disable_limit = isset($_POST['disable_limit']) ? true : false;

        // 隐藏前台用户统计板块
        $hide_user_statistics = isset($_POST['hide_user_statistics']) ? true : false;

        // 关闭评论
        $close_comments = isset($_POST['close_comments'])? true : false;

        // 容器ws-container样式
        $ws_weibo_container_max_width = sanitize_text_field($_POST['ws_weibo_container_max_width']);
        $ws_weibo_container_margin = sanitize_text_field($_POST['ws_weibo_container_margin']);
        $ws_weibo_container_padding = sanitize_text_field($_POST['ws_weibo_container_padding']);
        $ws_weibo_container_left_margin = sanitize_text_field($_POST['ws_weibo_container_left_margin']);

        // 微博公告和广告内容
        $weibo_announcement = wp_kses_post($_POST['weibo_announcement']);
        $weibo_advertisement = wp_kses_post($_POST['weibo_advertisement']);

        //左侧边栏内容
        $left_sidebar_advertisement = wp_kses_post($_POST['left_sidebar_advertisement']);

        // 模式设置 默认为固定
        $scroll_mode = isset($_POST['scroll_mode']) ? $_POST['scroll_mode'] : 'fixed';

        // 每一页的微博数量默认20
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 20;

        // 无权发布微博的复选框
        $allowed_roles = isset($_POST['ws_weibo_allowed_roles'])? $_POST['ws_weibo_allowed_roles'] : [];

        // 无权发布微博的自定义提示内容
        $unauthorized_message = wp_kses_post($_POST['ws_weibo_unauthorized_message']);

        // 自定义前台页面标题
        $ws_weibo_frontend_title = sanitize_text_field($_POST['ws_weibo_frontend_title']);

        // 关闭上传图片
        $disable_upload_image = isset($_POST['disable_upload_image']) ? true : false;

        // 左侧边栏随机文章
        $show_random_articles = isset($_POST['show_random_articles']) ? true : false;

        // 背景颜色
        $background_color = sanitize_hex_color($_POST['background_color']);

        // 联系方式
        $qq_number = sanitize_text_field($_POST['qq_number']);
        $custom_icon_url = esc_url_raw($_POST['custom_icon_url']);
        $city_name = sanitize_text_field($_POST['city_name']);
        $email_number = sanitize_text_field($_POST['email_number']);
        $bilibili_url = esc_url_raw($_POST['bilibili_url']);
        $bilibili_text = sanitize_text_field($_POST['bilibili_text']);
        $weibo_url = esc_url_raw($_POST['weibo_url']);
        $xiaohongshu_url = esc_url_raw($_POST['xiaohongshu_url']);
        $douyin_url = esc_url_raw($_POST['douyin_url']);
        $wangyiyun_url = esc_url_raw($_POST['wangyiyun_url']);        
        $weixin_qrcode_url = esc_url_raw($_POST['weixin_qrcode_url']);

        // 保存设置
        $settings = array(
            'start_time' => $start_time,  //开始时间
            'end_time' => $end_time,  //结束时间
            'disable_limit' => $disable_limit,   //取消时间
            'hide_user_statistics' => $hide_user_statistics,  //关闭统计板块
            'ws_weibo_container_max_width' => $ws_weibo_container_max_width,
            'ws_weibo_container_margin' => $ws_weibo_container_margin,
            'ws_weibo_container_padding' => $ws_weibo_container_padding,
            'ws_weibo_container_left_margin' => $ws_weibo_container_left_margin,
            'close_comments' => $close_comments,  //关闭评论
            'disable_upload_image' => $disable_upload_image,  //关闭图片上传
            'left_sidebar_advertisement' => $left_sidebar_advertisement,  //左侧边栏内容
            'scroll_mode' => $scroll_mode,  //模式选择
            'ws_weibo_frontend_title' => $ws_weibo_frontend_title  // 保存前台标题                
        );

        // 保存设置到数据库
        update_option(ws_weibo_WEIBO_POST_TIME_OPTION, $settings);
        update_option(ws_weibo_HIDE_USER_STATISTICS_OPTION, $settings);
        update_option(ws_weibo_CLOSE_COMMENTS_OPTION, $close_comments);

        // 更新侧边栏公告和广告
        update_option('ws_weibo_weibo_announcement', $weibo_announcement);
        update_option('ws_weibo_weibo_advertisement', $weibo_advertisement);
        update_option('ws_weibo_left_sidebar_advertisement', $left_sidebar_advertisement);

        // 每页微博数量
        update_option('ws_weibo_posts_per_page', $posts_per_page);

        //更新无权发布微博的选择
        update_option('ws_weibo_allowed_roles', $allowed_roles);

        //更新无权发布微博的自定义提示内容
        update_option('ws_weibo_unauthorized_message', $unauthorized_message);

        //左侧边栏随机文章
        update_option('ws_weibo_show_random_articles', $show_random_articles);

        //背景颜色
        update_option('ws_weibo_background_color', $background_color);

        //联系方式
        update_option('ws_weibo_qq_number', $qq_number);
        update_option('ws_weibo_custom_icon_url', $custom_icon_url);
        update_option('ws_weibo_city_name', $city_name);
        update_option('ws_weibo_email_number', $email_number);
        update_option('ws_weibo_bilibili_url', $bilibili_url);
        update_option('ws_weibo_bilibili_text', $bilibili_text);
        update_option('ws_weibo_weibo_url', $weibo_url);
        update_option('ws_weibo_xiaohongshu_url', $xiaohongshu_url);
        update_option('ws_weibo_douyin_url', $douyin_url);
        update_option('ws_weibo_wangyiyun_url', $wangyiyun_url);
        update_option('ws_weibo_weixin_qrcode_url', $weixin_qrcode_url); 

        echo "<div class='updated'><p>微博设置已更新。</p></div>";

    }

    // 获取默认设置
    $current_settings = get_option(ws_weibo_WEIBO_POST_TIME_OPTION, array(
        'start_time' => '',
        'end_time' => '',
        'disable_limit' => false,
        'hide_user_statistics' => false,
        'ws_weibo_container_max_width' => '800px',
        'ws_weibo_container_margin' => '1px',
        'ws_weibo_container_padding' => '20px',
        'ws_weibo_container_left_margin' => '10px',
        'close_comments' => false,
        'scroll_mode' => 'fixed',
        'disable_upload_image' => false,
        'ws_weibo_frontend_title' => '微心情 - 分享你的心情'  
    ));

    // 获取公告和广告等设置
    $announcement = get_option('ws_weibo_weibo_announcement', '');
    $advertisement = get_option('ws_weibo_weibo_advertisement', '');
    $left_sidebar_advertisement = get_option('ws_weibo_left_sidebar_advertisement', '');
    $posts_per_page = get_option('ws_weibo_posts_per_page', 20);
    $allowed_roles = get_option('ws_weibo_allowed_roles', []);
    $current_unauthorized_message = get_option('ws_weibo_unauthorized_message', '');

    // 默认背景颜色
    $background_color = get_option('ws_weibo_background_color', '#fff');

    // 联系方式
    $qq_number = get_option('ws_weibo_qq_number', '');
    $custom_icon_url = get_option('ws_weibo_custom_icon_url', '');
    $city_name = get_option('ws_weibo_city_name', '');
    $email_number = get_option('ws_weibo_email_number', '');
    $bilibili_url = get_option('ws_weibo_bilibili_url', '');
    $bilibili_text = get_option('ws_weibo_bilibili_text', '');
    $weibo_url = get_option('ws_weibo_weibo_url', '');
    $xiaohongshu_url = get_option('ws_weibo_xiaohongshu_url', '');
    $douyin_url = get_option('ws_weibo_douyin_url', '');
    $wangyiyun_url = get_option('ws_weibo_wangyiyun_url', '');
    $weixin_qrcode_url = get_option('ws_weibo_weixin_qrcode_url', '');

    ?>
    <style>
        /* 设置页面样式 */
       .ws-wrap {
            max-width: 99%;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }

        h2, h3 {
            color: #333;
            margin-bottom: 15px;
        }

        form input[type="text"],
        form input[type="checkbox"] {
            margin-bottom: 10px;
        }

        form label {
            display: inline-block;
            margin-bottom: 5px;
        }

        input[type="submit"].button-primary {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"].button-primary:hover {
            background-color: #005b82;
        }
    </style>    
    <div class="ws-wrap">
        <h2>微博常规设置</h2>
        <form method="POST" action="">
            <h3>发布时间限制</h3>
            <label for="start_time">允许发布微博的<span style="color:#009933;">开始时间：</span></label>
            <input type="text" name="start_time" id="start_time" value="<?php echo esc_attr($current_settings['start_time']);?>" placeholder="例如：01:00">
            <br>
            <label for="end_time">允许发布微博的<span style="color:#006699;">结束时间：</span></label>
            <input type="text" name="end_time" id="end_time" value="<?php echo esc_attr($current_settings['end_time']);?>" placeholder="例如：03:00">
            <br>
            <input type="checkbox" name="disable_limit" id="disable_limit" <?php checked($current_settings['disable_limit'], true);?>>
            <label for="disable_limit">取消时间限制（勾选后任何时间都可发布）</label>
            <br><p>格式：HH:mm，留空表示无限制</p><br>

            <h3>每页显示数量</h3>
            <label for="posts_per_page">每一页显示的微博数量：</label>
            <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_attr($posts_per_page); ?>" min="1" max="100">
            <br><br>

            <h3>前台页面标题</h3>
            <input type="text" name="ws_weibo_frontend_title" value="<?php echo esc_attr($current_settings['ws_weibo_frontend_title']); ?>" placeholder="输入前台页面标题" style="width: 100%; max-width: 500px; padding: 5px;">
            <br><br>

            <h3>其他选项</h3>
            <input type="checkbox" name="hide_user_statistics" id="hide_user_statistics" <?php checked($current_settings['hide_user_statistics'], true); ?>>
            <label for="hide_user_statistics">关闭前台用户统计板块</label>
            <br><br>

            <input type="checkbox" name="close_comments" id="close_comments" <?php checked($current_settings['close_comments'], true);?>>
            <label for="close_comments">关闭前台评论功能</label>
            <br><br>

            <input type="checkbox" name="disable_upload_image" id="disable_upload_image" <?php checked($current_settings['disable_upload_image'], true); ?>>
            <label for="disable_upload_image">关闭前台用户上传图片功能</label>
            <br><br>

            <input type="checkbox" name="show_random_articles" id="show_random_articles" <?php checked(get_option('ws_weibo_show_random_articles', false), true); ?>>
            <label for="show_random_articles">左侧边栏显示5篇随机文章</label>
            <br><br>


            <h3>允许发布微博的用户权限组</h3>
            <input type="checkbox" name="ws_weibo_allowed_roles[]" id="subscriber" value="subscriber" <?php if (in_array('subscriber', $allowed_roles)) echo 'checked';?> >
            <label for="subscriber">订阅者Subscriber</label>
            <br>
            <input type="checkbox" name="ws_weibo_allowed_roles[]" id="contributor" value="contributor" <?php if (in_array('contributor', $allowed_roles)) echo 'checked';?> >
            <label for="contributor">贡献者Contributor</label>
            <br>
            <input type="checkbox" name="ws_weibo_allowed_roles[]" id="author" value="author" <?php if (in_array('author', $allowed_roles)) echo 'checked';?> >
            <label for="author">作者Author</label>
            <br>
            <input type="checkbox" name="ws_weibo_allowed_roles[]" id="editor" value="editor" <?php if (in_array('editor', $allowed_roles)) echo 'checked';?> >
            <label for="editor">编辑Editor</label>
            <br>
            <input type="checkbox" name="ws_weibo_allowed_roles[]" id="administrator" value="administrator" <?php if (in_array('administrator', $allowed_roles)) echo 'checked';?> >
            <label for="administrator">管理员Administrator</label>
            <br><br>

            <h3>主框架模式</h3>
            <label for="scroll_mode">选择显示模式：</label>
            <select name="scroll_mode" id="scroll_mode">
                <option value="scroll" <?php selected($current_settings['scroll_mode'], 'scroll'); ?>>滚动模式</option>
                <option value="fixed" <?php selected($current_settings['scroll_mode'], 'fixed'); ?>>固定模式</option>
            </select>
            <br><br>
            <label for="background_color">主框架背景颜色：</label>
            <input type="text" name="background_color" id="background_color" value="<?php echo esc_attr($background_color); ?>" placeholder="例如：#faf8ff" style="width: 100%; max-width: 100px;">
            <br><br>

            <h3>微博列表板块样式</h3>
            <label for="ws_weibo_container_max_width">最大宽度 (max-width)：</label>
            <input type="text" name="ws_weibo_container_max_width" value="<?php echo esc_attr($current_settings['ws_weibo_container_max_width']); ?>" placeholder="例如: 800px 可以不填写">
            <br>
            <label for="ws_weibo_container_margin">外边距 (margin)：</label>
            <input type="text" name="ws_weibo_container_margin" value="<?php echo esc_attr($current_settings['ws_weibo_container_margin']); ?>" placeholder="例如: 10px 可以不填写">
            <br>
            <label for="ws_weibo_container_padding">内边距 (padding)：</label>
            <input type="text" name="ws_weibo_container_padding" value="<?php echo esc_attr($current_settings['ws_weibo_container_padding']); ?>" placeholder="例如: 20px 按需">
            <br>
            <label for="ws_weibo_container_left_margin">左边距 (左侧margin)：</label>
            <input type="text" name="ws_weibo_container_left_margin" value="<?php echo esc_attr($current_settings['ws_weibo_container_left_margin']); ?>" placeholder="例如: 10px 可以不填写">
            <br><br>


            <h3>右侧边栏 - 微博公告</h3>
            <textarea name="weibo_announcement" rows="5" cols="100" placeholder="输入公告内容"><?php echo esc_textarea(get_option('ws_weibo_weibo_announcement', '')); ?></textarea>
            <h3>右侧边栏 - 微博广告</h3>
            <textarea name="weibo_advertisement" rows="5" cols="100" placeholder="输入广告内容"><?php echo esc_textarea(get_option('ws_weibo_weibo_advertisement', '')); ?></textarea>
            <br><br>


            <h3>左侧边栏 - 广告推荐</h3>
            <textarea name="left_sidebar_advertisement" rows="5" cols="100" placeholder="输入左侧广告内容"><?php echo esc_textarea($left_sidebar_advertisement); ?></textarea>
            <br><br>            

            <h3>无权发布微博的提示内容</h3>
            <textarea name="ws_weibo_unauthorized_message" id="ws_weibo_unauthorized_message" rows="5" cols="100" placeholder="输入提示内容"><?php echo esc_textarea($current_unauthorized_message);?></textarea>
            <br><br>

            <h3>联系方式</h3>
            <label for="qq_number">QQ号:</label>
            <input type="text" name="qq_number" value="<?php echo esc_attr($qq_number); ?>" placeholder="请输入QQ号" style="width: 100%; max-width: 200px;">
            <br><br>
            <label for="city_name">城市:</label>
            <input type="text" name="city_name" value="<?php echo esc_attr($city_name); ?>" placeholder="请输入城市名称" style="width: 100%; max-width: 100px;">
            <br><br>
            <label for="email_number">邮箱:</label>
            <input type="text" name="email_number" value="<?php echo esc_attr($email_number); ?>" placeholder="请输入邮箱" style="width: 100%; max-width: 200px;">
            <br><br>            
            <label for="bilibili_url">B站链接:</label>
            <input type="text" name="bilibili_url" value="<?php echo esc_url($bilibili_url); ?>" placeholder="请输入B站链接" style="width: 100%; max-width: 500px;">
            <br><br>
            <label for="bilibili_text">B站链接文本:</label>
            <input type="text" name="bilibili_text" value="<?php echo esc_attr($bilibili_text); ?>" placeholder="请输入B站显示文本" style="width: 100%; max-width: 200px;">
            <br><br>
            <label for="weibo_url">微博链接:</label>
            <input type="text" name="weibo_url" value="<?php echo esc_url($weibo_url); ?>" placeholder="请输入微博链接" style="width: 100%; max-width: 500px;">
            <br><br>
            <label for="xiaohongshu_url">小红书链接:</label>
            <input type="text" name="xiaohongshu_url" value="<?php echo esc_url($xiaohongshu_url); ?>" placeholder="请输入小红书链接" style="width: 100%; max-width: 500px;">
            <br><br>            
            <label for="douyin_url">抖音链接:</label>
            <input type="text" name="douyin_url" value="<?php echo esc_url($douyin_url); ?>" placeholder="请输入抖音链接" style="width: 100%; max-width: 500px;">
            <br><br>
            <label for="wangyiyun_url">网易云音乐链接:</label>
            <input type="text" name="wangyiyun_url" value="<?php echo esc_url($wangyiyun_url); ?>" placeholder="请输入网易云音乐链接" style="width: 100%; max-width: 500px;">
            <br><br>
            <label for="weixin_qrcode_url">微信二维码图片地址:</label>
            <input type="text" name="weixin_qrcode_url" value="<?php echo esc_url($weixin_qrcode_url); ?>" placeholder="请输入微信二维码图片地址" style="width: 100%; max-width: 500px;">
            <br><br>                                                
            <label for="custom_icon_url">iconfont自定义图标样式链接:</label>
            <input type="text" name="custom_icon_url" value="<?php echo esc_attr($custom_icon_url); ?>" placeholder="请输入CSS链接" style="width: 100%; max-width: 500px;">
            <br><br>

            <input type="submit" name="submit_weibo_settings" value="保存设置" class="button-primary">
        </form>
        <p>1、公告和广告内容支持常见的HTML代码，所以添加广告图片、文字什么的都行。<br>2、侧边栏需要到网站小工具去添加，里面有一个微博右侧边栏、微博左侧边栏以及右侧边栏微博公告和广告、左侧边栏广告推荐的小工具，添加过去就行。<br>3、2个侧边栏都添加就是3栏模式，如果你只添加一个侧边栏，建议添加右侧边栏。<br>
        4、滚动模式 - 中间微博板块可以滑动，侧边栏单独固定在浏览器上，固定模式 - 不能滑动，全部固定。<br>
        5、微博列表板块按需设置吧，根据和你的主题页面属性。<br>6、设置不允许发布微博的用户组之后，但是他们依旧可以评论，写点提示内容，可以当留言板用（只有被封禁的用户，才不能评论）。<br>7、iconfont自定义图标样式链接必须要填写，不然前面就不显示对应的图标，参考教程：https://www.wujiit.com/iconfont</p>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            var updateMessage = document.querySelector('.updated');
            if (updateMessage) {
                updateMessage.style.display = 'none';
            }
        }, 1000); // 消失延迟1秒
    });
    </script>    
    <?php
}
//上面分别用了常量(时间发布/隐藏用户/评论)、直接存储(侧边栏内容)、数组存储(主框架/模式)3种代码方式，因为我在测试,不影响使用

// 前台背景颜色
function ws_weibo_apply_background_color() {
    // 确保当前页面包含短代码[ws_weibo_feeling]
    $post = get_post();  // 获取当前页面对象
    
    if ($post && has_shortcode($post->post_content, 'ws_weibo_feeling')) {
        $background_color = get_option('ws_weibo_background_color', '#fff');
        echo "<style>
            .ws-container, .ws-feeling-sidebar, .ws-feeling-left-sidebar {
                background-color: {$background_color};
            }
        </style>";
    }
}
add_action('wp_head', 'ws_weibo_apply_background_color');

// 前台微博页面主框架的样式
function ws_weibo_custom_container_style() {
    $settings = get_option(ws_weibo_WEIBO_POST_TIME_OPTION, array(
        'ws_weibo_container_max_width' => '800px',
        'ws_weibo_container_margin' => '0 auto',
        'ws_weibo_container_padding' => '20px',
        'ws_weibo_container_left_margin' => '0',
        'scroll_mode' => 'fixed'
    ));

    $max_width = esc_attr($settings['ws_weibo_container_max_width']);
    $margin = esc_attr($settings['ws_weibo_container_margin']);
    $padding = esc_attr($settings['ws_weibo_container_padding']);
    $left_margin = esc_attr($settings['ws_weibo_container_left_margin']);
    $scroll_mode = esc_attr($settings['scroll_mode']);

    $custom_css = "
        .ws-container {
            max-width: {$max_width};
            margin: {$margin};
            padding: {$padding};
            margin-left: {$left_margin};
            flex: 1;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-left: 5px;
            margin-right: 5px;
            height: 100%;
            overflow-y: auto;
        }
    ";

    // 根据选择的模式动态添加类
    if ($scroll_mode === 'scroll') {
        $custom_css .= "
        .ws-wbwrap {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            height: 100vh;
            overflow: auto;
            }
        ";
    } else {
        $custom_css .= "
        .ws-wbwrap {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            box-sizing: border-box;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow:  hidden;
            }
        ";
    }

    wp_add_inline_style('ws-feelings-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'ws_weibo_custom_container_style');

// 检查当前时间是否在允许发布微博的时间段内
function ws_weibo_is_within_post_time() {
    $settings = get_option(ws_weibo_WEIBO_POST_TIME_OPTION, array(
        'start_time' => '',
        'end_time' => '',
        'disable_limit' => false
    ));

    // 检查取消时间限制是否被勾选
    if (!empty($settings['disable_limit']) && $settings['disable_limit']) {
        return true; // 如果取消时间限制，直接允许发布
    }

    // 获取当前时间
    $current_time = current_time('H:i');
    $start_time = trim($settings['start_time']);
    $end_time = trim($settings['end_time']);

    // 如果开始时间和结束时间都为空，表示无限制
    if (empty($start_time) && empty($end_time)) {
        return true; // 没有时间限制，任何时间都可以发布
    }

    // 检查时间范围是否有效
    if (!empty($start_time) && !empty($end_time)) {
        return ($current_time >= $start_time && $current_time <= $end_time);
    }

    // 如果只设置了一个时间点（开始或结束），则不允许发布
    return false;
}


// 获取白名单的域名
function get_whitelist_domains() {
    $whitelist_domains = get_option('ws_weibo_image_whitelist', []);
    $current_site_host = parse_url(get_site_url(), PHP_URL_HOST);
    if (!in_array($current_site_host, $whitelist_domains)) {
        $whitelist_domains[] = $current_site_host;
    }
    return $whitelist_domains;
}

// 微博内容图片链接处理
function ws_weibo_process_content_images($content) {
    $whitelist_domains = get_whitelist_domains();

    $pattern = '/https?:\/\/[^\s]+?\.(jpg|jpeg|png|gif|bmp|webp)/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $images = $matches[0];
        $image_html = '<div class="ws-images">';

        foreach ($images as $image) {
            $parsed_url = parse_url($image);
            $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            // 仅处理白名单中的图片链接
            if (in_array($domain, $whitelist_domains)) {
                $image_html .= '<div class="ws-image-wrapper"><img src="' . esc_url($image) . '" alt="微心情动态说说" class="ws-image"></div>';
            } else {
                // 非白名单图片链接，直接不显示
                $image_html .= '';
            }
        }

        $image_html .= '</div>';
        $content = preg_replace($pattern, '', $content);
        $content .= $image_html;
    }

    return $content;
}

// 微博内容视频链接处理
function ws_weibo_process_content_videos($content) {
    $whitelist_domains = get_whitelist_domains();

    $pattern = '/https?:\/\/[^\s]+?\.(mp4|webm)/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $videos = $matches[0];
        $video_html = '<div class="ws-videos">';

        foreach ($videos as $video) {
            $parsed_url = parse_url($video);
            $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            // 仅处理白名单中的视频链接
            if (in_array($domain, $whitelist_domains)) {
                $video_type = (stripos($video, '.mp4') !== false) ? 'mp4' : 'webm';
                $video_html .= '<video controls class="ws-video"><source src="' . esc_url($video) . '" type="video/' . $video_type . '"></video>';
            } else {
                // 非白名单视频链接，直接不显示
                $video_html .= '';
            }
        }

        $video_html .= '</div>';
        $content = preg_replace($pattern, '', $content);
        $content .= $video_html;
    }

    return $content;
}

// 微博内容音频链接处理，支持mp3格式的播放
function ws_weibo_process_content_audio($content) {
    // 获取白名单的域名
    $whitelist_domains = get_option('ws_weibo_image_whitelist', []);
    
    // 获取当前站点的域名
    $current_site_host = parse_url(get_site_url(), PHP_URL_HOST);
    
    // 确保当前站点域名也在白名单中
    if (!in_array($current_site_host, $whitelist_domains)) {
        $whitelist_domains[] = $current_site_host;
    }

    // 正则表达式匹配MP3链接
    $pattern = '/https?:\/\/[^\s]+?\.mp3/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $audios = $matches[0]; // 获取所有MP3链接
        $audio_player_html = '';

        foreach ($audios as $audio) {
            // 解析音频链接的域名
            $parsed_url = parse_url($audio);
            $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

            // 检查音频链接是否在白名单内
            if (in_array($domain, $whitelist_domains)) {
                // 生成HTML5音频播放器代码
                $audio_player_html .= '
                    <audio controls>
                        <source src="' . esc_url($audio) . '" type="audio/mpeg">
                        您的浏览器不支持.
                    </audio>';
            } else {
                // 不在白名单内，显示音频链接
                $audio_player_html .= '<p>' . esc_html($audio) . '</p>';
            }
        }

        // 替换MP3链接为HTML播放器代码
        $content = preg_replace($pattern, '', $content); // 去除原始音频链接
        $content .= $audio_player_html; // 添加播放器代码
    }

    return $content;
}

// 微博内容中的网址，判断是否为当前网站的链接
function ws_weibo_process_content_links($content) {
    $processed_content = $content;
    $processed_content = ws_weibo_process_content_images($processed_content);
    $processed_content = ws_weibo_process_content_videos($processed_content);
    $processed_content = ws_weibo_process_content_audio($processed_content);

    $pattern = '/(http[s]?:\/\/[^\s]+)/i';
    preg_match_all($pattern, $processed_content, $matches);

    if (!empty($matches[0])) {
        $current_site_url = get_site_url();
        $content = str_replace('<br />', '__BR__', $content); // 临时替换所有 <br />

        foreach ($matches[0] as $url) {
            if (strpos($url, $current_site_url) === 0) {
                $content = str_replace($url, '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>', $content);
            } else {
                $content = str_replace($url, esc_html($url), $content); // 外部链接以纯文本显示
            }
        }

        $content = str_replace('__BR__', '<br />', $content); // 恢复 <br />
    }

    return $content;
}

// Bilibili视频解析
function ws_weibo_process_content_bilibili_videos($content) {
    // 匹配Bilibili视频链接
    //$pattern = '/https?:\/\/(?:www\.)?bilibili\.com\/video\/([a-zA-Z0-9]+)/i';
    $pattern = '/https?:\/\/(?:www\.)?bilibili\.com\/video\/([a-zA-Z0-9]+)(?:[\/?].*)?/i';
    preg_match_all($pattern, $content, $matches);


    if (!empty($matches[0])) {
        $videos = $matches[0]; // 获取Bilibili视频链接
        $video_html = '<div class="ws-bilibili-videos">';

        foreach ($matches[1] as $video_id) {
            // 嵌入iframe，展示Bilibili视频
            $iframe_url = "https://player.bilibili.com/player.html?bvid={$video_id}&page=1&autoplay=0";
            $video_html .= '<div class="ws-bilibili-video">';
            $video_html .= '<iframe src="' . esc_url($iframe_url) . '" 
                                 frameborder="0" 
                                 allowfullscreen="true" 
                                 scrolling="no"
                                 width="100%" 
                                 height="400px"
                                 class="ws-bilibili-iframe"></iframe>';
            $video_html .= '</div>';
        }

        $video_html .= '</div>';

        // 替换Bilibili视频链接为嵌入的HTML
        $content = preg_replace($pattern, '', $content); // 移除原始链接
        $content .= $video_html; // 添加视频展示的HTML
    }

    return $content;
}

// 检测网易云音乐链接并生成播放器
function ws_weibo_parse_netease_music($content) {
    $content = preg_replace_callback(
        '/https?:\/\/music\.163\.com\/(?:#\/)?song\?id=(\d+)(?:&.*)?/',
        function ($matches) {
            $song_id = $matches[1]; // 提取歌曲ID
            $iframe = '<iframe class="ws-music-iframe" frameborder="no" border="0" marginwidth="0" marginheight="0" width="330" height="86" src="//music.163.com/outchain/player?type=2&id=' . $song_id . '&auto=0&height=66"></iframe>';
            return $iframe; // 用iframe替换链接
        },
        $content
    );

    return $content;
}

// 对内容转义处理，排除图片和视频需要的标签
function ws_weibo_process_content_with_code_escape($content) {
    // 允许的HTML标签
    $allowed_tags = array(
        'audio' => array(
            'controls' => true,
            'autoplay' => true,
            'loop' => true,
            'muted' => true,
            'preload' => true,
            'class' => true,
        ),
        'source' => array(
            'src' => true,
            'type' => true,
        ),
        'video' => array(
            'controls' => true,
            'class' => true,
        ),
        'iframe' => array(
            'src' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
            'scrolling' => true,
            'class' => true,
        ),
        'div' => array(
            'class' => true,
        ),
        'p' => array(),
        'img' => array(
            'src' => true,
            'alt' => true,
            'class' => true,
            'width' => true,
            'display' => true,
            'margin' => true,
            'height' => true,
        ),
        'a' => array(
            'href' => true,
            'target' => true,
            'title' => true,
            'class' => true,
        ),
    );

    // wp_kses函数过滤内容
    $content = wp_kses($content, $allowed_tags);

    // 将换行符转换为<br>标签
    $content = nl2br($content);

    return $content;
}


// 修改微博内容处理函数，增加图片链接解析
function ws_weibo_process_content_with_media($content) {
    // 微博中判断是否为当前网站
    $content = ws_weibo_process_content_links($content);

    // 微博中的图片链接
    $content = ws_weibo_process_content_images($content);

    // 微博中的视频链接
    $content = ws_weibo_process_content_videos($content);

    // Bilibili视频解析
    $content = ws_weibo_process_content_bilibili_videos($content);

    // 网易云音乐解析
    $content = ws_weibo_parse_netease_music($content);

    // 处理音频链接
    $content = ws_weibo_process_content_audio($content);

    // 处理标签
    $content = ws_weibo_process_content_with_code_escape($content);

    // 屏蔽关键词
    return ws_weibo_process_blocked_keywords($content);
}

// AJAX回调函数，处理点赞操作
function ws_weibo_handle_like() {
    if (isset($_POST['feeling_id']) && is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix. 'ws_weibo_feelings';
        $feeling_id = intval($_POST['feeling_id']);
        $user_id = get_current_user_id();

        // 先查询当前微博的点赞数据，添加错误处理
        $feeling = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $feeling_id));
        if (!$feeling) {
            error_log('查询微博点赞数据失败，微博ID：'. $feeling_id);  // 记录错误日志
            wp_send_json_error(['message' => '查询微博数据失败']);
        }

        $liked_by = $feeling->liked_by;
        $liked_users = explode(',', $liked_by);
        $liked_user_index = array_search($user_id, $liked_users);
        if ($liked_user_index!== false) {
            // 用户已点赞过，取消点赞，减少点赞数量，移除点赞用户记录
            $new_likes_count = intval($feeling->likes_count) - 1;
            unset($liked_users[$liked_user_index]);
            $new_liked_by = implode(',', $liked_users);
            $wpdb->update(
                $table_name,
                [
                    'likes_count' => $new_likes_count,
                    'liked_by' => $new_liked_by
                ],
                ['id' => $feeling_id]
            );
            wp_send_json_success(['likes_count' => $new_likes_count]);
        } else {
            // 用户未点赞过，增加点赞数量，记录点赞用户
            $new_likes_count = intval($feeling->likes_count) + 1;
            $liked_users[] = $user_id;
            $new_liked_by = implode(',', $liked_users);

            // 更新数据库操作添加错误处理
            $result = $wpdb->update(
                $table_name,
                [
                    'likes_count' => $new_likes_count,
                    'liked_by' => $new_liked_by
                ],
                ['id' => $feeling_id]
            );
            if ($result === false) {
                error_log('更新微博点赞数据失败，微博ID：'. $feeling_id);  // 记录错误日志
                wp_send_json_error(['message' => '点赞操作更新数据库失败']);
            }
            wp_send_json_success(['likes_count' => $new_likes_count]);
        }
    }
    wp_send_json_error(['message' => '参数不合法或用户未登录']);
}
add_action('wp_ajax_ws_weibo_handle_like', 'ws_weibo_handle_like');
add_action('wp_ajax_nopriv_ws_weibo_handle_like', 'ws_weibo_handle_like');


// AJAX回调函数，处理删除微博操作
function ws_weibo_delete_feeling() {
    if (isset($_POST['feeling_id']) && is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix. 'ws_weibo_feelings';
        $feeling_id = intval($_POST['feeling_id']);
        $user_id = get_current_user_id();

        // 验证微博发布者，只有发布者有权删除
        $feeling = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $feeling_id));
        if (!$feeling || $feeling->user_id!= $user_id) {
            wp_send_json_error(['message' => '无权删除此微博']);
        }

        // 执行删除操作
        $result = $wpdb->delete($table_name, ['id' => $feeling_id]);
        if ($result) {
            wp_send_json_success(['message' => '微博删除成功']);
        } else {
            wp_send_json_error(['message' => '微博删除失败']);
        }
    }
    wp_send_json_error(['message' => '参数不合法或用户未登录']);
}
add_action('wp_ajax_ws_weibo_delete_feeling', 'ws_weibo_delete_feeling');
add_action('wp_ajax_nopriv_ws_weibo_delete_feeling', 'ws_weibo_delete_feeling');


// AJAX回调函数，处理删除用户自己全部微博的操作
function ws_weibo_delete_all_feelings() {
    if (isset($_POST['user_id']) && is_user_logged_in() && get_current_user_id() == intval($_POST['user_id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix. 'ws_weibo_feelings';
        $user_id = intval($_POST['user_id']);

        // 执行删除操作，根据用户ID删除所有微博记录
        $result = $wpdb->delete($table_name, ['user_id' => $user_id]);
        if ($result) {
            wp_send_json_success(['message' => '所有微博删除成功']);
        } else {
            wp_send_json_error(['message' => '删除全部微博失败']);
        }
    }
    wp_send_json_error(['message' => '参数不合法或用户未登录或无权操作']);
}
add_action('wp_ajax_ws_weibo_delete_all_feelings', 'ws_weibo_delete_all_feelings');
add_action('wp_ajax_nopriv_ws_weibo_delete_all_feelings', 'ws_weibo_delete_all_feelings');

//获取当前用户微博数量
function ws_weibo_get_user_weibo_count() {
    if (is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix. 'ws_weibo_feelings';
        $user_id = get_current_user_id();
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id));
    }
    return 0;
}

// 注册微博右侧边栏小工具
function ws_weibo_register_feeling_sidebar() {
    register_sidebar(array(
        'name'          => '微博右侧边栏',
        'id'            => 'ws_weibo_feeling_sidebar',
        'before_widget' => '<div class="ws-feeling-sidebar-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'ws_weibo_register_feeling_sidebar');

// 微博右侧边栏小工具显示微博公告和广告
class ws_weibo_Feeling_Announcement_Ad_Widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            'ws_weibo_feeling_announcement_ad_widget', // 侧边栏ID
            '微博右侧边栏公告和广告', // 侧边栏名称
            array('description' => '在微博右侧边栏显示公告和广告内容') // 侧边栏描述
        );
    }

    // 小工具显示内容
    public function widget($args, $instance) {
        $announcement = get_option('ws_weibo_weibo_announcement', '');
        $advertisement = get_option('ws_weibo_weibo_advertisement', '');

        //联系方式
        $qq_number = get_option('ws_weibo_qq_number', '');
        $custom_icon_url = get_option('ws_weibo_custom_icon_url', '');
        $city_name = get_option('ws_weibo_city_name', '');
        $email_number = get_option('ws_weibo_email_number', '');
        $bilibili_url = get_option('ws_weibo_bilibili_url', '');
        $bilibili_text = get_option('ws_weibo_bilibili_text', '');
        $weibo_url = get_option('ws_weibo_weibo_url', '');
        $xiaohongshu_url = get_option('ws_weibo_xiaohongshu_url', '');
        $douyin_url = get_option('ws_weibo_douyin_url', '');
        $wangyiyun_url = get_option('ws_weibo_wangyiyun_url', '');
        $weixin_qrcode_url = get_option('ws_weibo_weixin_qrcode_url', '');

        //必应壁纸
        $enable_bing_wallpaper = get_option('ws_weibo_enable_bing_wallpaper_option', false);

        echo $args['before_widget'];
        
        // 如果夜间模式启用，显示夜间模式开关
        ws_weibo_night_mode();

        // 如果QQ号或其他填写了，加载自定义图标样式链接
        if (!empty($qq_number) || !empty($city_name) || !empty($bilibili_url) || !empty($weibo_url) || !empty($douyin_url) || !empty($wangyiyun_url) || !empty($weixin_qrcode_url) || !empty($email_number) || !empty($xiaohongshu_url)) {
            if (!empty($custom_icon_url)) {
                echo '<link rel="stylesheet" href="' . esc_url($custom_icon_url) . '">';
            }
        }

        // 显示联系信息
        if (!empty($qq_number) || !empty($city_name) || !empty($bilibili_url) || !empty($weibo_url) || !empty($douyin_url) || !empty($wangyiyun_url) || !empty($weixin_qrcode_url) || !empty($email_number) || !empty($xiaohongshu_url)) {
            echo $args['before_title'] . '联系' . $args['after_title'];
            echo '<div class="ws-site-owner">';
            // 城市名称
            if (!empty($city_name)) {
                echo '<div class="ws-site-city"><i class="iconfont icon-weizhi"></i> ' . esc_html($city_name) . '</div>';
            }

            // QQ号
            if (!empty($qq_number)) {
                echo '<div class="ws-site-qq"><i class="iconfont icon-qq"></i> ' . esc_html($qq_number) . '</div>';
            }

            // 邮箱
            if (!empty($email_number)) {
                echo '<div class="ws-site-email"><i class="iconfont icon-email"></i> ' . esc_html($email_number) . '</div>';
            }

            // B站
            if (!empty($bilibili_url)) {
                echo '<div class="ws-site-bilibili"><i class="iconfont icon-Bzhan"></i> <a href="' . esc_url($bilibili_url) . '" target="_blank">' . esc_html($bilibili_text) . '</a></div>';
            }
            
            // 图标（微博、抖音、网易云音乐、微信）在同一排
            echo '<div class="ws-social-icons">';    

            // 微博使用微博图标作为链接
            if (!empty($weibo_url)) {
                echo '<div class="ws-site-weibo"><a href="' . esc_url($weibo_url) . '" target="_blank"><i class="iconfont icon-weibo"></i></a></div>';
            }

            // 小红书使用小红书图标作为链接
            if (!empty($xiaohongshu_url)) {
                echo '<div class="ws-site-xiaohongshu"><a href="' . esc_url($xiaohongshu_url) . '" target="_blank"><i class="iconfont icon-xiaohongshu"></i></a></div>';
            }

            // 抖音使用抖音图标作为链接
            if (!empty($douyin_url)) {
                echo '<div class="ws-site-douyin"><a href="' . esc_url($douyin_url) . '" target="_blank"><i class="iconfont icon-douyin"></i></a></div>';
            }

            // 网易云音乐使用网易云音乐图标作为链接
            if (!empty($wangyiyun_url)) {
                echo '<div class="ws-site-wangyiyun"><a href="' . esc_url($wangyiyun_url) . '" target="_blank"><i class="iconfont icon-wangyiyun"></i></a></div>';
            }

            // 微信图标和二维码
            if (!empty($weixin_qrcode_url)) {
                echo '<div class="ws-site-weixin">';
                echo '<a href="javascript:void(0)" class="ws-weixin-icon"><i class="iconfont icon-weixin"></i></a>';
                echo '<div class="ws-weixin-qrcode" style="display: none;">';
                // 添加class="no-lazy"确保二维码不受延迟加载影响
                echo '<img src="' . esc_url($weixin_qrcode_url) . '" alt="微信二维码" class="no-lazy">';
                echo '</div>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
        }

        // 显示公告
        if (!empty($announcement)) {
            echo $args['before_title'] . '公告' . $args['after_title'];
            echo '<div class="ws-announcement">' . wpautop($announcement) . '</div>';
        }

        // 显示广告
        if (!empty($advertisement)) {
            echo $args['before_title'] . '推荐' . $args['after_title'];
            echo '<div class="ws-advertisement">' . wpautop($advertisement) . '</div>';
        }

        // 显示必应壁纸
        if ($enable_bing_wallpaper) {
            $wallpaper_url = get_bing_wallpaper();
            if ($wallpaper_url) {
                echo $args['before_title'] . '壁纸' . $args['after_title'];
                echo '<div class="ws-bing-wallpaper"><img src="' . esc_url($wallpaper_url) . '" alt="壁纸"></div>';
            }
        }
        
        echo $args['after_widget'];

    }

    // 小工具后台设置
    public function form($instance) {
        
    }

    // 小工具更新功能
    public function update($new_instance, $old_instance) {
        return $new_instance;
    }
}

// 注册微博右侧边栏小工具
function ws_weibo_register_announcement_ad_widget() {
    register_widget('ws_weibo_Feeling_Announcement_Ad_Widget');
}
add_action('widgets_init', 'ws_weibo_register_announcement_ad_widget');

// 注册微博右侧边栏微信二维码
function ws_weibo_weixin_qrcode_script() {
    // 检查当前页面是否包含[ws_weibo_feeling]短代码，且已设置微信二维码URL
    $post = get_post();  // 获取当前页面对象

    // 确保有有效的页面对象且页面内容包含短代码，并且设置了微信二维码URL
    if ($post && has_shortcode($post->post_content, 'ws_weibo_feeling') && get_option('ws_weibo_weixin_qrcode_url')) {
        ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const weixinIcon = document.querySelector('.ws-weixin-icon');
            const qrcode = document.querySelector('.ws-weixin-qrcode');

            if (weixinIcon && qrcode) {
                weixinIcon.addEventListener('mouseenter', function() {
                    qrcode.style.display = 'block';  // 显示二维码
                });

                weixinIcon.addEventListener('mouseleave', function() {
                    qrcode.style.display = 'none';  // 隐藏二维码
                });
            }
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'ws_weibo_weixin_qrcode_script');


// 注册左边侧边栏微博小工具
function ws_weibo_register_feeling_left_sidebar() {
    register_sidebar(array(
        'name'          => '微博左侧边栏',
        'id'            => 'ws_weibo_feeling_left_sidebar',
        'before_widget' => '<div class="ws-feeling-left-sidebar-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'ws_weibo_register_feeling_left_sidebar');

// 左边侧边栏广告推荐小工具
class ws_weibo_Feeling_Left_Ad_Widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            'ws_weibo_feeling_left_ad_widget', // 侧边栏ID
            '微博左侧边栏广告推荐', // 侧边栏名称
            array('description' => '在微博左侧边栏显示内容') // 侧边栏描述
        );
    }

    // 左边侧边栏小工具显示内容
    public function widget($args, $instance) {
        $left_sidebar_advertisement = get_option('ws_weibo_left_sidebar_advertisement', '');

        // 获取设置中的API Key和复选框状态
        $api_key = get_option('ws_weibo_api_key_option', ''); // 获取笑话API Key
        $enable_jokes = get_option('ws_weibo_enable_jokes_option', false); // 获取笑话的设置
        $video_api_key = get_option('ws_weibo_video_api_key_option', ''); // 获取视频API Key
        $enable_videos = get_option('ws_weibo_enable_videos_option', false);// 获取视频的设置
        $history_today_api_key = get_option('ws_weibo_history_today_api_key_option', ''); // 获取历史上的今天API Key
        $enable_history_today = get_option('ws_weibo_enable_history_today_option', false); // 获取历史上的今天设置
        $weather_api_key = get_option('ws_weibo_weather_api_key_option', ''); // 获取天气api
        $enable_weather = get_option('ws_weibo_enable_weather_option', false); // 获取天气显示的设置

        echo $args['before_widget'];
        
        // 显示天气
        if ($enable_weather && !empty($weather_api_key)) {
            echo '<div class="ws-weather">';
            echo '<div id="tp-weather-widget"></div>';
            echo '<script>
            (function(a,h,g,f,e,d,c,b){b=function(){d=h.createElement(g);c=h.getElementsByTagName(g)[0];d.src=e;d.charset="utf-8";d.async=1;c.parentNode.insertBefore(d,c)};a["SeniverseWeatherWidgetObject"]=f;a[f]||(a[f]=function(){(a[f].q=a[f].q||[]).push(arguments)});a[f].l=+new Date();if(a.attachEvent){a.attachEvent("onload",b)}else{a.addEventListener("load",b,false)}}(window,document,"script","SeniverseWeatherWidget","//cdn.sencdn.com/widget2/static/js/bundle.js?t="+parseInt((new Date().getTime() / 100000000).toString(),10)));
            window.SeniverseWeatherWidget(\'show\', {
                flavor: "slim",
                location: "WX4FBXXFKE4F",
                geolocation: true,
                language: "zh-Hans",
                unit: "c",
                theme: "light",
                token: "' . esc_js($weather_api_key) . '",
                hover: "enabled",
                container: "tp-weather-widget"
            })
            </script>';
            echo '</div>';
        }

        // 如果时钟开启就显示
        ws_display_digital_clock();
        
        // 显示左侧广告
        if (!empty($left_sidebar_advertisement)) {
            echo $args['before_title'] . '推荐' . $args['after_title'];
            echo '<div class="ws-left-advertisement">' . wpautop($left_sidebar_advertisement) . '</div>';
        }

        // 如果启用随机文章，显示5篇随机文章
        if (get_option('ws_weibo_show_random_articles', false)) {
            $random_posts = get_posts(array(
                'numberposts' => 5, 
                'orderby' => 'rand',
                'post_status' => 'publish',
            ));
            if (!empty($random_posts)) {
                echo '<div class="ws-random-articles"><h3>随机文章推荐</h3><ul class="ws-random-list">';
                foreach ($random_posts as $post) {
                    echo '<li class="ws-random-item"><a href="' . get_permalink($post->ID) . '" target="_blank">' . esc_html($post->post_title) . '</a></li>';
                }
                echo '</ul></div>';
            }
        }

        // 如果启用了历史上的今天，显示一个随机事件
        if ($enable_history_today && !empty($history_today_api_key)) {
            $random_event = ws_weibo_get_random_history_today($history_today_api_key);
            if ($random_event) {
                $day = esc_html($random_event['day']);
                $date = esc_html($random_event['date']);
                $title = esc_html($random_event['title']);
                echo '<div class="ws-history-today"><h3>历史上的今天：</h3>';
                echo "<p><strong>{$date} - {$day}</strong>: <br>{$title}</p>";
                echo '</div>';
            } else {
                echo '<p>无法获取历史上的今天，请稍后再试。</p>';
            }
        }
        
        // 如果启用了随机笑话，随机显示一个
        if ($enable_jokes && !empty($api_key)) {
            $joke_content = ws_weibo_get_random_joke($api_key);
            if ($joke_content) {
                echo '<div class="ws-random-joke"><h3>随机笑话：</h3><p>' . esc_html($joke_content) . '</p></div>';
            } else {
                echo '<p>无法获取笑话，请稍后再试。</p>';
            }
        }

        // 如果启用了热门视频，显示3个
        if ($enable_videos && !empty($video_api_key)) {
            $videos = ws_weibo_get_hot_videos($video_api_key);
            if ($videos) {
                echo '<div class="ws-hot-videos"><h3>热门视频：</h3>';
                foreach ($videos as $video) {
                    $title = esc_html($video['title']);
                    $share_url = esc_url($video['share_url']);
                    echo "<p><a href='$share_url' target='_blank'>$title</a></p>";
                }
                echo '</div>';
            } else {
                echo '<p>无法获取热门视频，请稍后再试。</p>';
            }
        }
                
        echo $args['after_widget'];
    }

    // 小工具后台设置
    public function form($instance) {
        
    }

    // 小工具更新功能
    public function update($new_instance, $old_instance) {
        return $new_instance;
    }
}

// 注册左侧边栏小工具
function ws_weibo_register_left_ad_widget() {
    register_widget('ws_weibo_Feeling_Left_Ad_Widget');
}
add_action('widgets_init', 'ws_weibo_register_left_ad_widget');

// 前台微博页面
function ws_weibo_frontend_page() {
    ob_start();

    //获取标题
    $weibo_settings = get_option(ws_weibo_WEIBO_POST_TIME_OPTION, []); // 确保返回数组
    $ws_weibo_frontend_title = isset($weibo_settings['ws_weibo_frontend_title']) ? $weibo_settings['ws_weibo_frontend_title'] : '微心情 - 分享你的心情';

    // 确保ajaxurl可用
    echo '<script type="text/javascript">
        var ajaxurl = "'. admin_url('admin-ajax.php'). '";
    </script>';
?>
<div class="ws-wbwrap">
    <!-- 左侧边栏 -->
    <?php if (is_active_sidebar('ws_weibo_feeling_left_sidebar')): ?>
        <div class="ws-feeling-left-sidebar">
            <?php dynamic_sidebar('ws_weibo_feeling_left_sidebar'); ?>
        </div>
    <?php endif; ?>

    <div class="ws-container">
        <h2><?php echo esc_html($ws_weibo_frontend_title); ?></h2>
        <?php
        //获取隐藏统计设置
        $settings = get_option(ws_weibo_HIDE_USER_STATISTICS_OPTION, []); // 确保返回数组
        $hide_user_statistics = isset($settings['hide_user_statistics']) ? $settings['hide_user_statistics'] : false;

        // 统计板块
        if (is_user_logged_in() && !$hide_user_statistics) {
            $user_name = wp_get_current_user()->display_name;
            $user_avatar = get_avatar(get_current_user_id(), 40);  // 获取当前用户头像
            $weibo_count = ws_weibo_get_user_weibo_count();  // 获取当前用户微博数量
            echo '<div class="ws-statistics">';
            echo '<div class="ws-avatar">'. $user_avatar. '</div>';
            echo '<div class="ws-username">'. esc_html($user_name). '</div>';
            echo '<div class="ws-weibo-count">微博数量：'. esc_html($weibo_count). '</div>';
            echo '<button class="ws-delete-all-button" data-user-id="'. get_current_user_id(). '">删除全部微博</button>';
            echo '</div>';
        }
        ?>

        <?php
        // 获取允许发布微博的用户权限设置
        $allowed_roles = get_option('ws_weibo_allowed_roles', []);
        // 获取当前登录用户的角色
        $current_user = wp_get_current_user();
        $current_user_roles = $current_user->roles;
        $current_user_role = reset($current_user_roles);

        //获取关闭图片上传
        $disable_upload_image = isset($settings['disable_upload_image']) ? $settings['disable_upload_image'] : false;

        // 检查当前时间是否在允许发布微博的时间段内
        if (!ws_weibo_is_within_post_time()) {
            echo "<div class='ws-unauthorized-message'><p>当前不在发布微博的时间范围呢，请等待开放。</p></div>";
        } else {
            // 判断当前用户是否有权限发布微博
            if (!in_array($current_user_role, $allowed_roles)) {
                // 获取无权用户发布微博内容提示框的内容
                $unauthorized_message = get_option('ws_weibo_unauthorized_message', '你没有权限发布微博哦');
                echo "<div class='ws-unauthorized-message'>$unauthorized_message</div>";
            } else {
                // 若在时间范围内且有权限，显示发布表单
                if (is_user_logged_in()) {
                    // 检查用户是否被禁止发布微博
                    $banned_users = get_option('ws_weibo_banned_users', []);
                    if (in_array(get_current_user_id(), $banned_users)) {
                        echo "<div class='ws-unauthorized-message'><p>你已被禁止发布微博和评论。</p></div>";
                    } else {
                        ?>
                        <!-- 发布微博表单 -->
                        <form id="ws-weibo-form">
                            <?php wp_nonce_field('ws_weibo_post_action', 'ws_weibo_post_nonce'); ?>
                            <textarea name="ws_weibo_content" placeholder="发布你的心情..." required></textarea><br>

                            <?php if (!$disable_upload_image) : ?>
                                <!-- 图片上传部分 -->
                                <div class="ws-image-upload-container">
                                    <!-- 隐藏浏览器默认文件输入框 -->
                                    <input type="file" id="ws-image-upload" name="ws_images[]" accept=".jpg, .jpeg, .png, .gif, .webp" multiple style="display:none;">
                                    <!-- 自定义按钮触发文件选择 -->
                                    <button type="button" id="ws-select-images-button">选择图片</button>
                                    <button type="button" id="ws-add-more-images" style="display:none;">继续添加</button>
                                    <div id="ws-uploading-message">正在上传中...</div>
                                    <div id="ws_image_message" style="display:none;"></div>
                                    <div id="ws-image-preview-container"></div>
                                </div>
                            <?php endif; ?>

                            <input type="submit" value="发布">
                        </form>
                        <?php
                    }
                }
            }
        }
        ?>

        <!-- 微博内容区域 -->
        <div id="ws-feelings-container"></div>

        <!-- 分页导航 -->
        <div class="ws-pagination" id="ws-pagination"></div>

        <!-- AJAX分页加载的JavaScript -->
        <script>
        jQuery(document).ready(function ($) {
            var currentPage = 1;
            var postsPerPage = <?php echo get_option('ws_weibo_posts_per_page', 20); ?>;
            var isLoading = false;

            // 加载微博内容
            function loadFeelings(page) {
                if (isLoading) return;
                isLoading = true;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ws_weibo_load_feelings',
                        page: page,
                        posts_per_page: postsPerPage
                    },
                    beforeSend: function() {
                        $('#ws-feelings-container').append('<div class="ws-loading">加载中...</div>');
                    },
                    success: function(response) {
                        $('.ws-loading').remove();
                        if (response.success) {
                            // 清空容器并添加新内容
                            if (page === 1) {
                                $('#ws-feelings-container').empty();
                            }
                            $('#ws-feelings-container').append(response.data.feelings_html);
                            
                            // 更新分页导航
                            updatePagination(response.data.total_pages, page);
                        } else {
                            $('#ws-feelings-container').append('<p>暂无心情记录。</p>');
                            $('#ws-pagination').empty();
                        }
                        isLoading = false;
                    },
                    error: function() {
                        $('.ws-loading').remove();
                        $('#ws-feelings-container').append('<p>加载失败，请重试。</p>');
                        isLoading = false;
                    }
                });
            }

            // 更新分页导航
            function updatePagination(totalPages, currentPage) {
                var paginationHtml = '';
                if (totalPages > 1) {
                    // 首页
                    if (currentPage > 1) {
                        paginationHtml += '<a href="#" class="ws-pagination-link" data-page="1">首页</a>';
                        paginationHtml += '<a href="#" class="ws-pagination-link" data-page="' + (currentPage - 1) + '">上一页</a>';
                    }
                    // 页码
                    for (var i = 1; i <= totalPages; i++) {
                        paginationHtml += '<a href="#" class="' + (currentPage == i ? 'current' : '') + ' ws-pagination-link" data-page="' + i + '">' + i + '</a>';
                    }
                    // 下一页、末页
                    if (currentPage < totalPages) {
                        paginationHtml += '<a href="#" class="ws-pagination-link" data-page="' + (currentPage + 1) + '">下一页</a>';
                        paginationHtml += '<a href="#" class="ws-pagination-link" data-page="' + totalPages + '">最后一页</a>';
                    }
                }
                $('#ws-pagination').html(paginationHtml);

                // 绑定分页点击事件
                $('.ws-pagination-link').off('click').on('click', function(e) {
                    e.preventDefault();
                    var page = $(this).data('page');
                    currentPage = page;
                    loadFeelings(page);
                });
            }

            // 初始加载第一页
            loadFeelings(currentPage);

            // 图片上传区域
            var allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];  // 允许的文件格式
            var maxFileSize = 2 * 1024 * 1024;  // 2MB
            var imageUrls = []; // 存储已上传的图片URL

            // 点击自定义按钮时触发文件输入框
            $('#ws-select-images-button').on('click', function() {
                $('#ws-image-upload').click();  // 触发隐藏的文件输入框
            });

            // 选择文件后检查文件格式
            $('#ws-image-upload').on('change', function(event) {
                var files = event.target.files;
                if (files.length > 0) {
                    // 检查文件格式和大小
                    var invalidFiles = [];
                    var largeFiles = [];
                    $.each(files, function(index, file) {
                        if (!allowedMimeTypes.includes(file.type)) {
                            invalidFiles.push(file.name);
                        }
                        if (file.size > maxFileSize) {
                            largeFiles.push(file.name);
                        }
                    });

                    if (invalidFiles.length > 0) {
                        $('#ws_image_message').text('不支持的文件格式: ' + invalidFiles.join(', ') + '. 只支持JPG、JPEG、PNG、GIF、WebP格式。').removeClass().addClass('error-message').show();
                        $('#ws-image-upload').val('');  // 清空选择的文件
                    } else if (largeFiles.length > 0) {
                        $('#ws_image_message').text('文件过大: ' + largeFiles.join(', ') + '. 只支持最大2MB的图片。').removeClass().addClass('error-message').show();
                        $('#ws-image-upload').val('');  // 清空选择的文件
                    } else {
                        // 没有无效文件，继续处理图片上传
                        handleImageUpload(files);
                    }
                }
            });

            // 继续添加按钮
            $('#ws-add-more-images').on('click', function() {
                $('#ws-image-upload').click();
            });

            // 图片上传处理函数
            function handleImageUpload(files) {
                var formData = new FormData();
                for (var i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }
                formData.append('action', 'ws_weibo_handle_image_upload');
                formData.append('security', '<?php echo wp_create_nonce("image_upload_nonce"); ?>');

                // 显示“正在上传中”提示
                $('#ws-uploading-message').show();

                // 清空之前的消息
                $('#ws_image_message').hide().text('');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        // 隐藏“正在上传中”提示
                        $('#ws-uploading-message').hide();

                        if (response.success) {
                            response.data.files.forEach(function(file) {
                                imageUrls.push(file.url);
                                displayImagePreview(file.url);
                            });
                            $('#ws-add-more-images').show();
                        } else {
                            $('#ws_image_message').text('图片上传失败: ' + response.data.message).removeClass().addClass('error-message').show();
                        }
                    },
                    error: function() {
                        // 隐藏“正在上传中”提示
                        $('#ws-uploading-message').hide();
                        $('#ws_image_message').text('上传图片时出现错误').removeClass().addClass('error-message').show();
                    }
                });
            }

            // 显示图片预览
            function displayImagePreview(url) {
                var previewHtml = `
                <div class="ws-image-preview-item">
                    <img src="${url}" alt="微心情" class="ws-image-preview" />
                    <button class="ws-delete-image" onclick="removeImagePreview(this)">×</button>
                </div>`;
                $('#ws-image-preview-container').append(previewHtml);
            }

            // 删除图片预览
            window.removeImagePreview = function(button) {
                $(button).closest('.ws-image-preview-item').remove();
                // 从imageUrls数组中移除已删除图片的URL
                var index = imageUrls.indexOf($(button).prev().attr('src'));
                if (index !== -1) {
                    imageUrls.splice(index, 1);
                }
            }

            // AJAX 提交微博表单
            $('#ws-weibo-form').on('submit', function(e) {
                e.preventDefault();

                var content = $("textarea[name='ws_weibo_content']").val();
                // 将图片URL添加到内容中
                imageUrls.forEach(function(url) {
                    content += `\n${url}`;
                });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ws_weibo_handle_frontend_post',
                        ws_weibo_submit: true,
                        ws_weibo_content: content,
                        ws_weibo_post_nonce: $('#ws_weibo_post_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            // 清空表单
                            $("textarea[name='ws_weibo_content']").val('');
                            $('#ws-image-preview-container').empty();
                            imageUrls = [];
                            $('#ws-add-more-images').hide();
                            $('#ws_image_message').hide();

                            // 重新加载第一页
                            currentPage = 1;
                            loadFeelings(currentPage);
                        } else {
                            // 显示错误消息
                            var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-top: 10px;">' + response.data.message + '</div>');
                            $('#ws-weibo-form').append($message);
                            setTimeout(function() {
                                $message.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            }, 2000);
                        }
                    },
                    error: function() {
                        // 显示错误消息
                        var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-top: 10px;">发布失败，请重试</div>');
                        $('#ws-weibo-form').append($message);
                        setTimeout(function() {
                            $message.fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 2000);
                    }
                });
            });

            // 点赞、删除、评论等事件委托
            $('#ws-feelings-container').on('click', '.ws-like-section', function(event) {
                if (!$(event.target).closest('.ws-like-section').length) {
                    return;
                }
                var that = this;
                var feelingId = $(this).closest('.ws-feeling').data('feeling-id');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ws_weibo_handle_like',
                        feeling_id: feelingId
                    },
                    success: function(response) {
                        if (response.success) {
                            $(that).find('.like-count').text(response.data.likes_count + " 赞");
                        }
                    },
                    error: function() {
                        console.log('点赞请求出错');
                    }
                });
            });

            $('#ws-feelings-container').on('click', '.ws-delete-button', function(event) {
                event.preventDefault();
                var feelingId = $(this).data('id');
                var $this = $(this);
                var $promptBox = $('<div class="ws-delete-prompt-box">' +
                    '<p>确定要删除这条微博吗？</p>' +
                    '<button class="ws-delete-confirm-button">删除</button>' +
                    '<button class="ws-delete-cancel-button">取消</button>' +
                    '</div>');
                $('body').append($promptBox);
                $promptBox.find('.ws-delete-confirm-button').click(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ws_weibo_delete_feeling',
                            feeling_id: feelingId
                        },
                        success: function(response) {
                            if (response.success) {
                                currentPage = 1;
                                loadFeelings(currentPage);
                            } else {
                                console.log('删除微博失败');
                            }
                        },
                        error: function() {
                            console.log('删除微博请求出错');
                        }
                    });
                    $promptBox.remove();
                });
                $promptBox.find('.ws-delete-cancel-button').click(function() {
                    $promptBox.remove();
                });
            });

            $('#ws-feelings-container').on('click', '.ws-comment-section', function() {
                var feelingId = $(this).closest('.ws-feeling').data('feeling-id');
                var $commentInputSection = $(this).next('.ws-comment-input-section');
                var $commentSection = $(this).next('.ws-comment-input-section').next('.ws-comment-list');

                $commentInputSection.toggle();
                $commentSection.toggle();

                if ($commentSection.is(':empty')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ws_weibo_load_comments',
                            feeling_id: feelingId
                        },
                        success: function(response) {
                            if (response.success) {
                                var comments = response.data.comments;
                                $commentSection.empty();
                                comments.forEach(function(comment) {
                                    var commentTime = new Date(comment.timestamp);
                                    var formattedTime = commentTime.toLocaleString();
                                    $commentSection.append("<div class='ws-comment'>" + comment.content +
                                        "<span class='ws-comment-author'> - " + comment.author + "</span>" +
                                        "<span class='ws-comment-time'> (" + formattedTime + ")</span></div>");
                                });
                            }
                        },
                        error: function() {
                            console.log('加载评论失败');
                        }
                    });
                }
            });

            $('#ws-feelings-container').on('click', '.ws-submit-comment', function() {
                var feelingId = $(this).closest('.ws-feeling').data('feeling-id');
                var commentContent = $(this).prev('.ws-comment-input').val();
                var $submitButton = $(this);

                if (commentContent.trim() === '') {
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ws_weibo_submit_comment',
                        feeling_id: feelingId,
                        comment_content: commentContent
                    },
                    success: function(response) {
                        if (response.success) {
                            $submitButton.prev('.ws-comment-input').val('');
                            var $message = $('<div class="ws-message" style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论成功!</div>');
                            $submitButton.after($message);
                            setTimeout(function() {
                                $message.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            }, 1000);

                            setTimeout(function() {
                                var $commentSection = $submitButton.closest('.ws-feeling').find('.ws-comment-list');
                                $commentSection.empty();
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'ws_weibo_load_comments',
                                        feeling_id: feelingId
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            var comments = response.data.comments;
                                            comments.forEach(function(comment) {
                                                var commentTime = new Date(comment.timestamp);
                                                var formattedTime = commentTime.toLocaleString();
                                                $commentSection.append("<div class='ws-comment'>" + comment.content +
                                                    "<span class='ws-comment-author'> - " + comment.author + "</span>" +
                                                    "<span class='ws-comment-time'> (" + formattedTime + ")</span></div>");
                                            });
                                        }
                                    },
                                    error: function() {
                                        console.log('加载评论失败');
                                    }
                                });
                            }, 50);

                            setTimeout(function() {
                                var $commentCountSection = $submitButton.closest('.ws-feeling').find('.ws-comment-count');
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'ws_weibo_get_comment_count',
                                        feeling_id: feelingId
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $commentCountSection.text(response.data.comment_count);
                                        }
                                    },
                                    error: function() {
                                        console.log('更新评论数量失败');
                                    }
                                });
                            }, 1000);
                        } else {
                            var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论失败，请重试</div>');
                            $submitButton.after($message);
                            setTimeout(function() {
                                $message.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            }, 1000);
                        }
                    },
                    error: function() {
                        var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论失败，请重试</div>');
                        $submitButton.after($message);
                        setTimeout(function() {
                            $message.fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 1000);
                    }
                });
            });

            // 删除全部微博
            $('.ws-delete-all-button').click(function(event) {
                event.preventDefault();
                var $this = $(this);
                var $promptBox = $("<div class='ws-delete-all-prompt-box'>" +
                    "<p>确定要删除你所有的微博记录吗？</p>" +
                    "<button class='ws-delete-all-confirm-button'>删除全部</button>" +
                    "<button class='ws-delete-all-cancel-button'>取消</button>" +
                    "</div>");
                $this.after($promptBox);
                $promptBox.find(".ws-delete-all-confirm-button").click(function() {
                    var userId = $this.data("user-id");
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "ws_weibo_delete_all_feelings",
                            user_id: userId
                        },
                        success: function(response) {
                            if (response.success) {
                                currentPage = 1;
                                loadFeelings(currentPage);
                            } else {
                                console.log("删除全部微博失败");
                            }
                        },
                        error: function() {
                            console.log("删除全部微博请求出错");
                        }
                    });
                    $promptBox.remove();
                });
                $promptBox.find(".ws-delete-all-cancel-button").click(function() {
                    $promptBox.remove();
                });
            });
        });
        </script>
    </div>
    <!-- 右侧边栏 -->
    <?php if (is_active_sidebar('ws_weibo_feeling_sidebar')): ?>
        <div class="ws-feeling-sidebar">
            <?php dynamic_sidebar('ws_weibo_feeling_sidebar'); ?>
        </div>
    <?php endif; ?>
</div>
<?php
    return ob_get_clean();
}
add_shortcode('ws_weibo_feeling', 'ws_weibo_frontend_page');

// 处理图片上传的AJAX请求
function ws_weibo_handle_image_upload() {
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        wp_send_json_error(['message' => '没有选择文件']);
    }

    $uploaded_files = [];
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // 限制文件格式
    $max_file_size = 2 * 1024 * 1024; // 最大2MB

    foreach ($_FILES['files']['name'] as $key => $file_name) {
        $file = [
            'name'     => $file_name,
            'type'     => $_FILES['files']['type'][$key],
            'tmp_name' => $_FILES['files']['tmp_name'][$key],
            'error'    => $_FILES['files']['error'][$key],
            'size'     => $_FILES['files']['size'][$key],
        ];

        // 检查文件格式
        if (!in_array($file['type'], $allowed_mime_types)) {
            wp_send_json_error(['message' => '不支持的文件格式。只支持JPG、JPEG、PNG、GIF、WebP格式。']);
        }

        // 检查文件大小
        if ($file['size'] > $max_file_size) {
            wp_send_json_error(['message' => '文件过大。请确保文件大小不超过2MB。']);
        }
        
        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }

        // 生成附件
        $file_url = $upload['url'];
        $attachment = [
            'guid' => $file_url,
            'post_mime_type' => wp_check_filetype($upload['file'])['type'],
            'post_title' => sanitize_file_name(basename($upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
        ];
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }

        $uploaded_files[] = ['url' => $file_url];
    }

    wp_send_json_success(['files' => $uploaded_files]);
}
add_action('wp_ajax_ws_weibo_handle_image_upload', 'ws_weibo_handle_image_upload');
add_action('wp_ajax_nopriv_ws_weibo_handle_image_upload', 'ws_weibo_handle_image_upload');

// 获取用户组发布微博
function ws_weibo_extend_post_capabilities() {
    if (isset($_POST['ws_weibo_submit'])) {
        // 检查用户角色
        $user = wp_get_current_user();
        $allowed_roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
        if (array_intersect($allowed_roles, $user->roles)) {
            return true;
        }
    }
    return false;
}

// 处理前台发布微博
function ws_weibo_handle_frontend_post() {
    if (isset($_POST['ws_weibo_submit']) && (current_user_can('publish_posts') || ws_weibo_extend_post_capabilities())) {
        // 验证 Nonce
        if (!isset($_POST['ws_weibo_post_nonce']) || !wp_verify_nonce($_POST['ws_weibo_post_nonce'], 'ws_weibo_post_action')) {
            wp_send_json_error(['message' => '安全检查失败，请重试。']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ws_weibo_feelings';

        // 检查用户是否被禁止发布微博
        $banned_users = get_option('ws_weibo_banned_users', []);
        if (in_array(get_current_user_id(), $banned_users)) {
            wp_send_json_error(['message' => '你已被禁止发布微博和评论。']);
        }

        $content = sanitize_textarea_field($_POST['ws_weibo_content']);
        
        // 将换行符转换为<br>标签
        $content = nl2br($content);
        
        // 插入数据
        $result = $wpdb->insert($table_name, [
            'user_id' => get_current_user_id(),
            'content' => $content,
            'timestamp' => current_time('mysql')
        ]);

        if ($result) {
            wp_send_json_success(['message' => '微博发布成功']);
        } else {
            wp_send_json_error(['message' => '微博发布失败']);
        }
    }
}
add_action('wp_ajax_ws_weibo_handle_frontend_post', 'ws_weibo_handle_frontend_post');
add_action('wp_ajax_nopriv_ws_weibo_handle_frontend_post', 'ws_weibo_handle_frontend_post');

// 处理AJAX加载微博请求
function ws_weibo_load_feelings() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 20;
    $offset = ($page - 1) * $posts_per_page;

    global $wpdb;
    $table_name = $wpdb->prefix . 'ws_weibo_feelings';

    // 查询微博内容
    $feelings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d, %d",
            $offset, $posts_per_page
        )
    );

    $feelings_html = '';

    if ($feelings) {
        foreach ($feelings as $feeling) {
            // 获取微博的评论数量
            $comment_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ws_weibo_comments WHERE feeling_id = %d",
                    $feeling->id
                )
            );

            // 获取用户信息
            $user_info = get_userdata($feeling->user_id);
            $user_name = $user_info ? $user_info->display_name : '匿名用户';
            $user_avatar = get_avatar($feeling->user_id, 40);

            // 获取用户的author页面URL
            $author_url = get_author_posts_url($feeling->user_id);

            // 微博的HTML结构
            $feelings_html .= "<div class='ws-feeling' data-feeling-id='". esc_attr($feeling->id). "'>";
            $feelings_html .= "<div class='ws-user'>";
            $feelings_html .= "<div class='ws-avatar'>$user_avatar</div>";
            $feelings_html .= "<div class='ws-username'><a href='". esc_url($author_url). "' target='_blank' style='text-decoration: none;'>". esc_html($user_name). "</a></div>";
            $feelings_html .= "</div>";
            $feelings_html .= "<div class='ws-content'>";
            $feelings_html .= "<p>". ws_weibo_process_content_with_media($feeling->content). "</p>";
            $feelings_html .= "</div>";

            $feelings_html .= "<div class='ws-like-timestamp-section'>";
            $feelings_html .= "<div class='ws-like-section' style='cursor: pointer; display: inline-block;'>";
            $feelings_html .= "&#128077;";  // 点赞图标
            $feelings_html .= "<span class='like-count'>". esc_html($feeling->likes_count). " 赞</span>";
            $feelings_html .= "</div>";
            $feelings_html .= "<div class='ws-timestamp' style='display: inline-block;'>". esc_html($feeling->timestamp). "</div>";

            if (get_current_user_id() == $feeling->user_id) {
                $feelings_html .= '<div class="ws-delete-section" style="display: inline-block; cursor: pointer;">';
                $feelings_html .= '<a href="#" class="ws-delete-button" data-id="'. esc_attr($feeling->id). '">删除</a>';
                $feelings_html .= '</div>';
            }
            $feelings_html .= "</div>";

            // 获取关闭评论设置项的值
            $close_comments = get_option(ws_weibo_CLOSE_COMMENTS_OPTION, false);
            if (!$close_comments) {
                // 评论按钮
                $feelings_html .= "<div class='ws-comment-section' style='cursor: pointer; display: inline-flex; align-items: center;'>";
                $feelings_html .= "&#128172;";  // 评论图标
                // 如果评论数量大于0，显示评论数量
                if ($comment_count > 0) {
                    $feelings_html .= "<div class='ws-comment-count' style='margin-left: 5px;'>" . esc_html($comment_count) . "</div>";
                }
                $feelings_html .= "</div>";

                // 隐藏的评论输入框和提交按钮
                $feelings_html .= "<div class='ws-comment-input-section' style='display: none;'>";
                $feelings_html .= "<textarea class='ws-comment-input' placeholder='输入评论...'></textarea>";
                $feelings_html .= "<button class='ws-submit-comment'>提交评论</button>";
                $feelings_html .= "</div>";

                // 评论列表
                $feelings_html .= "<div class='ws-comment-list' style='display: none;'></div>";
            } else {
                $feelings_html .= "<style>.ws-comment-section,.ws-comment-input-section,.ws-comment-list { display: none!important; }</style>";
            }

            $feelings_html .= "</div>";  // 结束每条微博的展示
        }

        // 获取总页数
        $total_feelings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_feelings / $posts_per_page);

        wp_send_json_success([
            'feelings_html' => $feelings_html,
            'total_pages' => $total_pages
        ]);
    } else {
        wp_send_json_error(['message' => '暂无心情记录']);
    }
}
add_action('wp_ajax_ws_weibo_load_feelings', 'ws_weibo_load_feelings');
add_action('wp_ajax_nopriv_ws_weibo_load_feelings', 'ws_weibo_load_feelings');


// 卸载插件
register_uninstall_hook(__FILE__, 'ws_weibo_uninstall');

// 删掉数据
function ws_weibo_uninstall() {
    // 删除选项
    $options = [
        'ws_weibo_weibo_post_time',
        'ws_weibo_hide_user_statistics',
        'ws_weibo_close_comments',
        'ws_weibo_weibo_announcement',
        'ws_weibo_weibo_advertisement',
        'ws_weibo_left_sidebar_advertisement',
        'ws_weibo_api_key_option',
        'ws_weibo_enable_jokes_option',
        'ws_weibo_video_api_key_option',
        'ws_weibo_enable_videos_option',
        'ws_weibo_history_today_api_key_option',
        'ws_weibo_enable_history_today_option',
        'ws_weibo_weather_api_key_option',
        'ws_weibo_blocked_keywords',
        'ws_weibo_image_whitelist',
        'ws_weibo_unauthorized_message',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}
