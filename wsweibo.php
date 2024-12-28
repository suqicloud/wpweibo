<?php
/*
 * Plugin Name: 小半微心情
 * Plugin URI: https://www.jingxialai.com/4307.html
 * Description: 心情动态说说前台用户版，支持所有用户发布心情，点赞，评论，白名单等常规设置。
 * Version: 1.8
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

// 插件激活时创建前台页面
function ws_weibo_create_feeling_page_on_activation() {
    // 检查是否已有页面存在
    $page = get_page_by_path('ws-feeling');
    
    // 如果页面不存在，则创建新页面
    if (!$page) {
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
}
register_activation_hook(__FILE__, 'ws_weibo_create_feeling_page_on_activation');

// 加载CSS文件，仅在短代码页面加载
function ws_weibo_enqueue_styles() {
    if (has_shortcode(get_post()->post_content, 'ws_weibo_feeling')) {
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

            <h3>隐藏选项</h3>
            <input type="checkbox" name="hide_user_statistics" id="hide_user_statistics" <?php checked($current_settings['hide_user_statistics'], true); ?>>
            <label for="hide_user_statistics">隐藏前台用户统计板块</label>
            <br><br>

            <input type="checkbox" name="close_comments" id="close_comments" <?php checked($current_settings['close_comments'], true);?>>
            <label for="close_comments">关闭前台评论功能</label>
            <br><br>

            <input type="checkbox" name="disable_upload_image" id="disable_upload_image" <?php checked($current_settings['disable_upload_image'], true); ?>>
            <label for="disable_upload_image">关闭前台用户上传图片功能</label>
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

            <input type="submit" name="submit_weibo_settings" value="保存设置" class="button-primary">
        </form>
        <p>1、公告和广告内容支持常见的HTML代码，所以添加广告图片、文字什么的都行。<br>2、侧边栏需要到网站小工具去添加，里面有一个微博右侧边栏、微博左侧边栏以及右侧边栏微博公告和广告、左侧边栏广告推荐的小工具，添加过去就行。<br>3、2个侧边栏都添加就是3栏模式，如果你只添加一个侧边栏，建议添加右侧边栏。<br>
        4、滚动模式 - 中间微博板块可以滑动，侧边栏单独固定在浏览器上，固定模式 - 不能滑动，全部固定。<br>
        5、微博列表板块按需设置吧，根据和你的主题页面属性。<br>
    6、设置不允许发布微博的用户组之后，但是他们依旧可以评论，写点提示内容，可以当留言板用（只有被封禁的用户，才不能评论）。</p>
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
            background-color: #fff;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-left: 5px;
            margin-right: 5px;
            height: 100%;
            overflow-y: auto; /* 主容器允许垂直滚动 */
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
            position: relative; /* 侧边栏的滚动受控 */
            height: 100vh; /* 高度为视口高度 */
            overflow: auto; /* 允许滚动 */
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
            overflow:  hidden; /* 不允许滚动 */
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


// 微博内容图片链接处理
function ws_weibo_process_content_images($content) { 
    // 获取白名单的域名
    $whitelist_domains = get_option('ws_weibo_image_whitelist', []);

    // 正则表达式匹配图片链接
    $pattern = '/https?:\/\/[^\s]+?\.(jpg|jpeg|png|gif|bmp|webp)/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $images = $matches[0]; // 获取所有图片链接
        $image_html = '<div class="ws-images">';

        // 处理图片分组的函数
        $process_images_group = function($image_group) use ($whitelist_domains) {
            $group_html = '';
            foreach ($image_group as $image) {
                // 解析图片链接的域名
                $parsed_url = parse_url($image);
                $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

                // 检查图片链接是否在白名单内
                if (in_array($domain, $whitelist_domains)) {
                    $group_html .= '<img src="' . esc_url($image) . '" alt="微心情动态说说" class="ws-image">';
                } else {
                    // 不在白名单内，显示图片链接
                    $group_html .= '<p>' . esc_html($image) . '</p>';
                }
            }
            return $group_html;
        };

        // 根据图片数量决定如何分组
        $chunked_images = array_chunk($images, 3, true); // 按照每3张图片分组
        foreach ($chunked_images as $index => $image_group) {
            $row_class = (count($image_group) == 1) ? 'ws-image-row-1' : (count($image_group) == 2 ? 'ws-image-row-2' : 'ws-image-row-3');
            $image_html .= '<div class="ws-image-row ' . $row_class . '">';
            $image_html .= $process_images_group($image_group);  // 处理当前分组的图片
            $image_html .= '</div>';
        }

        $image_html .= '</div>';

        // 替换图片链接
        $content = preg_replace($pattern, '', $content); // 先去除原始图片链接
        $content .= $image_html; // 在最后添加图片显示的html
    }

    return $content;
}

// 微博内容视频链接处理，支持mp4和webm格式
function ws_weibo_process_content_videos($content) {
    // 获取白名单的域名
    $whitelist_domains = get_option('ws_weibo_image_whitelist', []);

    // 正则表达式匹配视频链接
    $pattern = '/https?:\/\/[^\s]+?\.(mp4|webm)/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $videos = $matches[0]; // 获取所有视频链接
        $video_html = '<div class="ws-videos">';

        // 处理视频分组的函数
        $process_videos_group = function($video_group) use ($whitelist_domains) {
            $group_html = '';
            foreach ($video_group as $video) {
                // 解析视频链接的域名
                $parsed_url = parse_url($video);
                $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

                // 检查视频链接是否在白名单内
                if (in_array($domain, $whitelist_domains)) {
                    $video_type = (stripos($video, '.mp4') !== false) ? 'mp4' : 'webm';
                    $group_html .= '<video controls class="ws-video">
                                        <source src="' . esc_url($video) . '" type="video/' . $video_type . '">
                                      </video>';
                } else {
                    // 不在白名单内，显示视频链接
                    $group_html .= '<p>' . esc_html($video) . '</p>';
                }
            }
            return $group_html;
        };

        // 根据视频数量决定如何分组
        $chunked_videos = array_chunk($videos, 1, true); // 按照每1个视频分组
        foreach ($chunked_videos as $index => $video_group) {
            $video_html .= $process_videos_group($video_group);  // 处理当前分组的视频
        }

        $video_html .= '</div>';

        // 替换视频链接
        $content = preg_replace($pattern, '', $content); // 先去除原始视频链接
        $content .= $video_html; // 在最后添加视频显示的html
    }

    return $content;
}

// 微博内容中的网址，判断是否为当前网站的链接
function ws_weibo_process_content_links($content) {
    // 正则表达式匹配网址
    $pattern = '/(http[s]?:\/\/[^\s]+)/i';
    preg_match_all($pattern, $content, $matches);

    if (!empty($matches[0])) {
        $current_site_url = get_site_url();  // 获取当前网站URL

        foreach ($matches[0] as $url) {
            // 判断是否为当前网站的URL
            if (strpos($url, $current_site_url) === 0) {
                // 当前网站的链接，自动加上超链接
                $content = str_replace($url, '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>', $content);
            } else {
                // 外部网站，不添加超链接
                $content = str_replace($url, esc_html($url), $content);
            }
        }
    }

    return $content;
}

// 对内容转义处理，排除图片和视频需要的标签
function ws_weibo_process_content_with_code_escape($content) {
    // 允许的HTML标签
    $allowed_tags = array(
        'video' => array(
            'controls' => true,
            'class' => true
        ),
        'source' => array(
            'src' => true,
            'type' => true
        ),
        'iframe' => array(
            'src' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
            'scrolling' => true,
            'class' => true
        ),
        'div' => array(
            'class' => true
        ),
        'p' => array(),
        'img' => array(
            'src' => true,
            'alt' => true,
            'class' => true,
            'width' => true,
            'display' => true,
            'margin' => true,
            'height' => true
        ),
        'a' => array(
            'href' => true,
            'target' => true,
            'title' => true,
            'class' => true
        )
    );

    // wp_kses函数过滤内容
    $content = wp_kses($content, $allowed_tags);

    return $content;
}

// 修改微博内容处理函数，增加图片链接解析
function ws_weibo_process_content_with_media($content) {
    // 微博中的全部链接（包括图片链接和视频链接）
    $content = ws_weibo_process_content_links($content);

    // 微博中的图片链接
    $content = ws_weibo_process_content_images($content);

    // 微博中的视频链接
    $content = ws_weibo_process_content_videos($content);

    // Bilibili视频解析
    $content = ws_weibo_process_content_bilibili_videos($content);

    // 网易云音乐解析
    $content = ws_weibo_parse_netease_music($content);

    // 屏蔽关键词
    $content = ws_weibo_process_content_with_code_escape($content);
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
        '/https?:\/\/music\.163\.com\/#\/song\?id=(\d+)/',
        function ($matches) {
            $song_id = $matches[1]; // 提取歌曲ID
            $iframe = '<iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="330" height="86" src="//music.163.com/outchain/player?type=2&id=' . $song_id . '&auto=0&height=66"></iframe>';
            return $iframe; // 用iframe替换链接
        },
        $content
    );

    return $content;
}

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

        echo $args['before_widget'];

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

        echo $args['before_widget'];

        // 显示左侧广告
        if (!empty($left_sidebar_advertisement)) {
            echo $args['before_title'] . '推荐' . $args['after_title'];
            echo '<div class="ws-left-advertisement">' . wpautop($left_sidebar_advertisement) . '</div>';
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
    $ws_weibo_frontend_title = get_option(ws_weibo_WEIBO_POST_TIME_OPTION)['ws_weibo_frontend_title'];

    // 确保ajaxurl可用
    echo '<script type="text/javascript">
        var ajaxurl = "'. admin_url('admin-ajax.php'). '";
    </script>';

    // 显示成功消息
    if (get_transient('ws_weibo_success_message')) {
        echo "<div class='notice notice-success'><p>发布成功！</p></div>";
        delete_transient('ws_weibo_success_message'); // 消息显示后立刻清除
    }
       
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
        $settings = get_option(ws_weibo_HIDE_USER_STATISTICS_OPTION);
        // 统计板块
        if (is_user_logged_in() && !$settings['hide_user_statistics']) {
            $user_name = wp_get_current_user()->display_name;
            $user_avatar = get_avatar(get_current_user_id(), 40);  // 获取当前用户头像
            $weibo_count = ws_weibo_get_user_weibo_count();  // 获取当前用户微博数量
            echo '<div class="ws-statistics">';
            echo '<div class="ws-avatar">'. $user_avatar. '</div>';
            echo '<div class="ws-username">'. esc_html($user_name). '</div>';
            echo '<div class="ws-weibo-count">微博数量：'. esc_html($weibo_count). '</div>';
            echo '<button class="ws-delete-all-button" data-user-id="'. get_current_user_id(). '">删除全部微博</button>';
            echo '</div>';
            // AJAX请求处理用JavaScript来实现删除
            echo '<script>
                jQuery(document).ready(function ($) {
                    $(".ws-delete-all-button").click(function (event) {
                        event.preventDefault();
                        var $this = $(this);
                        // 创建自定义提示框元素
                        var $promptBox = $("<div class=\'ws-delete-all-prompt-box\'>" +
                            "<p>确定要删除你所有的微博记录吗？</p>" +
                            "<button class=\'ws-delete-all-confirm-button\'>删除全部</button>" +
                            "<button class=\'ws-delete-all-cancel-button\'>取消</button>" +
                            "</div>");
                        $this.after($promptBox);
                        // 处理删除全部按钮点击事件
                        $promptBox.find(".ws-delete-all-confirm-button").click(function () {
                            var userId = $this.data("user-id");
                            $.ajax({
                                url: ajaxurl,
                                type: "POST",
                                data: {
                                    action: "ws_weibo_delete_all_feelings",
                                    user_id: userId
                                },
                                success: function (response) {
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        console.log("删除全部微博失败");
                                    }
                                },
                                error: function () {
                                    console.log("删除全部微博请求出错");
                                }
                            });
                            // 移除提示框
                            $promptBox.remove();
                        });
                        // 处理取消按钮点击事件
                        $promptBox.find(".ws-delete-all-cancel-button").click(function () {
                            $promptBox.remove();
                        });
                    });
                });
            </script>';
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
        <form action="" method="post" id="ws_weibo_form" enctype="multipart/form-data">
            <?php wp_nonce_field('ws_weibo_post_action', 'ws_weibo_post_nonce'); ?>
            <textarea name="ws_weibo_content" placeholder="发布你的心情..." required></textarea><br>

            <?php if (!$disable_upload_image) : ?>
                <!-- 上传区域的容器 -->
                <div class="ws-weibo-upload-area">
                    <label for="ws_weibo_image" class="custom-upload-btn">选择图片</label>
                    <input type="file" name="ws_weibo_image" id="ws_weibo_image" accept="image/*"><br>
                    <div id="ws_image_message"></div>
                    <!-- 图片预览 -->
                    <div id="ws_image_preview"></div>
                </div>
            <?php endif; ?>

            <input type="submit" name="ws_weibo_submit" value="发布">
        </form>
                    <?php
                }
            }
        }
    }
    ?>

        <?php
        // 获取每页显示微博的数量
        $posts_per_page = get_option('ws_weibo_posts_per_page', 20);

        // 获取当前页
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $offset = ($paged - 1) * $posts_per_page;

        global $wpdb;
        $table_name = $wpdb->prefix. 'ws_weibo_feelings';

        // 查询微博内容
        $feelings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d, %d",
                $offset, $posts_per_page
            )
        );

        if ($feelings) {
            foreach ($feelings as $feeling) {

            // 获取微博的评论数量
                global $wpdb;
                $comment_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}ws_weibo_comments WHERE feeling_id = %d",
                        $feeling->id
                    )
                );

                // 获取用户信息
                $user_info = get_userdata($feeling->user_id);
                $user_name = $user_info? $user_info->display_name : '匿名用户';
                $user_avatar = get_avatar($feeling->user_id, 40);

                // 获取用户的author页面URL
                $author_url = get_author_posts_url($feeling->user_id);

                // 微博的HTML结构
                echo "<div class='ws-feeling' data-feeling-id='". esc_attr($feeling->id). "'>";
                echo "<div class='ws-user'>";
                echo "<div class='ws-avatar'>$user_avatar</div>";
                echo "<div class='ws-username'><a href='". esc_url($author_url). "' target='_blank' style='text-decoration: none;'>". esc_html($user_name). "</a></div>";
                echo "</div>";
                echo "<div class='ws-content'>";
                echo "<p>". ws_weibo_process_content_with_media($feeling->content). "</p>";
                echo "</div>";

                echo "<div class='ws-like-timestamp-section'>";
                echo "<div class='ws-like-section' style='cursor: pointer; display: inline-block;'>";
                echo "&#128077; ";  // 点赞图标
                echo "<span class='like-count'>". esc_html($feeling->likes_count). " 赞</span>";
                echo "</div>";
                echo "<div class='ws-timestamp' style='display: inline-block;'>". esc_html($feeling->timestamp). "</div>";

                if (get_current_user_id() == $feeling->user_id) {
                    echo '<div class="ws-delete-section" style="display: inline-block; cursor: pointer;">';
                    echo '<a href="#" class="ws-delete-button" data-id="'. esc_attr($feeling->id). '">删除</a>';
                    echo '</div>';
                }
                echo "</div>";

            // 获取关闭评论设置项的值
            $close_comments = get_option(ws_weibo_CLOSE_COMMENTS_OPTION, false);
            if (!$close_comments) {

            // 评论按钮
            echo "<div class='ws-comment-section' style='cursor: pointer; display: inline-flex; align-items: center;'>";
            echo "&#128172;";  // 评论图标

            // 如果评论数量大于0，显示评论数量
            if ($comment_count > 0) {
            echo "<div class='ws-comment-count' style='margin-left: 5px;'>" . esc_html($comment_count) . "</div>";
        }
        echo "</div>";

                // 隐藏的评论输入框和提交按钮
                echo "<div class='ws-comment-input-section' style='display: none;'>";
                echo "<textarea class='ws-comment-input' placeholder='输入评论...'></textarea>";
                echo "<button class='ws-submit-comment'>提交评论</button>";
                echo "</div>";

                // 评论列表
                echo "<div class='ws-comment-list' style='display: none;'></div>";
            } else {
                // 关闭评论时
                echo "<style>.ws-comment-section,.ws-comment-input-section,.ws-comment-list { display: none!important; }</style>";
            }

            echo "</div>";  // 结束每条微博的展示

          
            }

            // 显示分页链接
            $total_feelings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $total_pages = ceil($total_feelings / $posts_per_page);

            if ($total_pages > 1) {
            echo '<div class="ws-pagination">';
            // 首页、上一页、页码、下一页、最后一页
            if ($paged > 1) {
                echo '<a href="' . get_pagenum_link(1) . '" class="ws-pagination-link">首页</a>';
                echo '<a href="' . get_pagenum_link($paged - 1) . '" class="ws-pagination-link">上一页</a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<a href="' . get_pagenum_link($i) . '" class="' . ($paged == $i ? 'current' : '') . '">' . $i . '</a>';
            }
            if ($paged < $total_pages) {
                echo '<a href="' . get_pagenum_link($paged + 1) . '" class="ws-pagination-link">下一页</a>';
                echo '<a href="' . get_pagenum_link($total_pages) . '" class="ws-pagination-link">最后一页</a>';
            }
            echo '</div>';
        }
    } else {
        echo "<p>暂无心情记录。</p>";
    }

        // 添加JavaScript代码来处理点赞和删除微博的AJAX请求
 ?>
        <script>
            jQuery(document).ready(function ($) {
                $('.ws-like-section').click(function (event) {
                    // 点击点赞图标区域触发AJAX请求
                    if (!$(event.target).closest('.ws-like-section').length) {
                        return;
                    }
                    var that = this;  // 保存当前点击元素的this指向
                    var feelingId = $(this).closest('.ws-feeling').data('feeling-id');  // 通过数据属性获取微博ID
                    $.ajax({
                        url: ajaxurl,  // AJAX处理URL
                        type: 'POST',
                        data: {
                            action: 'ws_weibo_handle_like',
                            feeling_id: feelingId
                        },
                        success: function (response) {
                            if (response.success) {
                                // 使用保存的that来更新点赞数量
                                $(that).find('.like-count').text(response.data.likes_count + " 赞");
                            }
                        },
                        error: function () {
                            console.log('点赞请求出错');
                        }
                    });
                });

                // 处理删除单条微博的AJAX请求
                $('.ws-delete-button').click(function (event) {
                    event.preventDefault();
                    var feelingId = $(this).data('id');
                    var $this = $(this);
                    // 创建自定义删除提示框元素
                    var $promptBox = $('<div class="ws-delete-prompt-box">' +
                        '<p>确定要删除这条微博吗？</p>' +
                        '<button class="ws-delete-confirm-button">删除</button>' +
                        '<button class="ws-delete-cancel-button">取消</button>' +
                        '</div>');
                    $('body').append($promptBox);
                    // 处理删除按钮点击事件
                    $promptBox.find('.ws-delete-confirm-button').click(function () {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'ws_weibo_delete_feeling',
                                feeling_id: feelingId
                            },
                            success: function (response) {
                                if (response.success) {
                                    // 刷新页面
                                    location.reload();
                                } else {
                                    console.log('删除微博失败');
                                }
                            },
                            error: function () {
                                console.log('删除微博请求出错');
                            }
                        });
                        // 移除提示框
                        $promptBox.remove();
                    });
                    // 处理取消按钮点击事件
                    $promptBox.find('.ws-delete-cancel-button').click(function () {
                        $promptBox.remove();
                    });
                });

                // 获取评论
                $('.ws-comment-section').click(function () {
                    var feelingId = $(this).closest('.ws-feeling').data('feeling-id');
                    var $commentInputSection = $(this).next('.ws-comment-input-section');
                    var $commentSection = $(this).next('.ws-comment-input-section').next('.ws-comment-list');

                    // 切换评论输入框和评论列表的显示状态
                    $commentInputSection.toggle();  // 切换评论输入框显示
                    $commentSection.toggle();       // 切换评论列表显示


                    // 加载评论
                    if ($commentSection.is(':empty')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'ws_weibo_load_comments',
                                feeling_id: feelingId
                            },
                            success: function (response) {
                                if (response.success) {
                                    var comments = response.data.comments;
                                    // 清空当前评论列表
                                    $commentSection.empty();

                                    // 循环展示已有评论
                                    comments.forEach(function (comment) {
                                        var commentTime = new Date(comment.timestamp);
                                        var formattedTime = commentTime.toLocaleString();  // 格式化时间

                                        $commentSection.append("<div class='ws-comment'>" + comment.content + 
                                            "<span class='ws-comment-author'> - " + comment.author + "</span>" + 
                                            "<span class='ws-comment-time'> (" + formattedTime + ")</span></div>");
                                    });
                                }
                            },
                            error: function () {
                                console.log('加载评论失败');
                            }
                        });
                    }
                });


                // 处理评论
                $('.ws-submit-comment').click(function () {
                    var feelingId = $(this).closest('.ws-feeling').data('feeling-id');
                    var commentContent = $(this).prev('.ws-comment-input').val();
                    var $submitButton = $(this);

                    // 如果评论内容为空，则不执行后续操作
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
                        success: function (response) {
                            if (response.success) {
                            // 清空评论输入框
                                $submitButton.prev('.ws-comment-input').val('');
                                // 评论成功的提示
                                var $message = $('<div class="ws-message" style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论成功!</div>');
                                $submitButton.after($message); // 提示消息在按钮旁边

                                // 1秒后移除提示框
                                setTimeout(function () {
                                    $message.fadeOut(500, function () {
                                        $(this).remove();
                                    });
                                }, 1000);

                                // 延迟50毫秒后重新加载评论列表
                                setTimeout(function () {
                                    var $commentSection = $submitButton.closest('.ws-feeling').find('.ws-comment-list');
                                    $commentSection.empty(); // 先清空评论列表

                                    // 然后加载最新的评论列表
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'ws_weibo_load_comments',
                                            feeling_id: feelingId
                                        },
                                        success: function (response) {
                                            if (response.success) {
                                                var comments = response.data.comments;
                                                // 循环展示已有评论
                                                comments.forEach(function (comment) {
                                                    var commentTime = new Date(comment.timestamp);
                                                    var formattedTime = commentTime.toLocaleString();  // 格式化时间

                                                    $commentSection.append("<div class='ws-comment'>" + comment.content + 
                                                        "<span class='ws-comment-author'> - " + comment.author + "</span>" + 
                                                        "<span class='ws-comment-time'> (" + formattedTime + ")</span></div>");
                                                });
                                            }
                                        },
                                        error: function () {
                                            console.log('加载评论失败');
                                        }
                                    });
                                }, 50);

                        // 1秒后更新评论数量
                        setTimeout(function () {
                            var $commentCountSection = $submitButton.closest('.ws-feeling').find('.ws-comment-count');
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'ws_weibo_get_comment_count',
                                    feeling_id: feelingId
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $commentCountSection.text(response.data.comment_count);
                                    }
                                },
                                error: function () {
                                    console.log('更新评论数量失败');
                                }
                            });
                        }, 1000);                
            } else {
                // 失败提交评论后的提示消息
                var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论失败，请重试</div>');
                $submitButton.after($message); // 提示消息在按钮旁边

                // 1秒后移除提示框
                setTimeout(function () {
                    $message.fadeOut(500, function () {
                        $(this).remove();
                    });
                }, 1000);
            }
        },
        error: function () {
            // 其他原因导致的错误消息
            var $message = $('<div class="ws-message" style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; margin-left: 10px; display: inline-block;">评论失败，请重试</div>');
            $submitButton.after($message); // 提示消息在按钮旁边

            // 1秒后移除提示框
            setTimeout(function () {
                $message.fadeOut(500, function () {
                    $(this).remove();
                });
            }, 1000);
        }
    });
});
        // 图片上传区域
        $('#ws_weibo_image').on('change', function () {
            var file = this.files[0];
            var validFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // 支持的图片格式
            var maxSize = 2 * 1024 * 1024; // 最大文件大小 2MB

            // 消息显示
            var messageContainer = $('#ws_image_message');

            // 清空消息
            messageContainer.text('');

            // 检查图片格式
            if ($.inArray(file.type, validFormats) === -1) {
                messageContainer.text('只允许上传 JPG, PNG, GIF, WebP 格式的图片。');
                $(this).val(''); // 清除文件输入框
                return;
            }

            // 检查图片大小
            if (file.size > maxSize) {
                messageContainer.text('图片大小不能超过 5MB。');
                $(this).val(''); // 清除文件输入框
                return;
            }

            // 图片预览
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#ws_image_preview').html('<img src="' + e.target.result + '" style="max-width: 100px; max-height: 100px;">');
            };
            reader.readAsDataURL(file);
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
        // 验证Nonce
        if (!isset($_POST['ws_weibo_post_nonce']) || !wp_verify_nonce($_POST['ws_weibo_post_nonce'], 'ws_weibo_post_action')) {
            wp_die('安全检查失败，请重试。');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ws_weibo_feelings';

        // 检查用户是否被禁止发布微博
        $banned_users = get_option('ws_weibo_banned_users', []);
        if (in_array(get_current_user_id(), $banned_users)) {
            wp_die('你已被禁止发布微博和评论。');
        }

        // 获取微博内容
        $content = sanitize_text_field($_POST['ws_weibo_content']);

        // 处理上传的图片
        $attachment_url = '';
        if (isset($_FILES['ws_weibo_image']) && !empty($_FILES['ws_weibo_image']['name'])) {
            $file = $_FILES['ws_weibo_image'];
            $file_type = wp_check_filetype($file['name']);
            $file_size = $file['size'];
            $valid_formats = ['jpg', 'jpeg', 'png', 'gif', 'webp']; // 支持的图片格式
            $max_size = 2 * 1024 * 1024; // 最大文件大小 2MB

            // 验证图片格式
            if (!in_array(strtolower($file_type['ext']), $valid_formats)) {
                // 返回错误信息
                set_transient('ws_weibo_image_error', '只允许上传 JPG, PNG, GIF, WebP 格式的图片。', 30);
                wp_safe_redirect(remove_query_arg(['ws_weibo_message', 'success'], wp_get_referer()));
                exit;
            }

            // 验证图片大小
            if ($file_size > $max_size) {
                // 返回错误信息
                set_transient('ws_weibo_image_error', '图片大小不能超过 2MB。', 30);
                wp_safe_redirect(remove_query_arg(['ws_weibo_message', 'success'], wp_get_referer()));
                exit;
            }

            // 上传图片到媒体库
            $upload = wp_handle_upload($file, ['test_form' => false]);

            if (isset($upload['url'])) {
                // 图片上传成功，获取图片URL
                $attachment_url = $upload['url'];

                // 将图片插入到媒体库
                $wp_filetype = wp_check_filetype($upload['file'], null);
                $attachment = array(
                    'guid' => $upload['url'], 
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                // 插入数据库
                $attachment_id = wp_insert_attachment($attachment, $upload['file']);

                // 生成元数据
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
            }
        }

        // 如果有图片，添加到内容中
        if ($attachment_url) {
            $content .= '<img src="' . esc_url($attachment_url) . '" alt="微心情图片">';
        }

        // 插入数据到数据库
        $wpdb->insert($table_name, [
            'user_id' => get_current_user_id(),
            'content' => $content,
            'timestamp' => current_time('mysql')
        ]);

        // 设置临时成功消息
        set_transient('ws_weibo_success_message', true, 30);

        // 重定向回当前页面（清理POST数据）
        wp_safe_redirect(remove_query_arg(['ws_weibo_message', 'success'], wp_get_referer()));
        exit;
    }
}

add_action('init', 'ws_weibo_handle_frontend_post');


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
        'ws_weibo_blocked_keywords',
        'ws_weibo_image_whitelist',
        'ws_weibo_unauthorized_message',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}
