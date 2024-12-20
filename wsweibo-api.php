<?php
/*
 * 微博心情说说 - apikey
*/

// apikey设置页面
function ws_weibo_api_settings_page() {
    if (isset($_POST['submit_api_settings'])) {
        // 验证nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ws_weibo_api_settings_nonce')) {
            die('非法请求');
        }

        // 获取表单中的数据
        $api_key = sanitize_text_field($_POST['api_key']);
        $enable_jokes = isset($_POST['enable_jokes']) ? true : false;
        $video_api_key = sanitize_text_field($_POST['video_api_key']);
        $enable_videos = isset($_POST['enable_videos']) ? true : false;
        $history_today_api_key = sanitize_text_field($_POST['history_today_api_key']);
        $enable_history_today = isset($_POST['enable_history_today']) ? true : false;


        // 保存设置
        update_option('ws_weibo_api_key_option', $api_key);
        update_option('ws_weibo_enable_jokes_option', $enable_jokes);
        update_option('ws_weibo_video_api_key_option', $video_api_key);
        update_option('ws_weibo_enable_videos_option', $enable_videos);
        update_option('ws_weibo_history_today_api_key_option', $history_today_api_key);
        update_option('ws_weibo_enable_history_today_option', $enable_history_today);


        echo "<div class='updated'><p>API设置已更新。</p></div>";
    }

    // 获取当前API设置
    $current_api_key = get_option('ws_weibo_api_key_option', '');
    $current_enable_jokes = get_option('ws_weibo_enable_jokes_option', false);
    $current_video_api_key = get_option('ws_weibo_video_api_key_option', '');
    $current_enable_videos = get_option('ws_weibo_enable_videos_option', false);
    $current_history_today_api_key = get_option('ws_weibo_history_today_api_key_option', '');
    $current_enable_history_today = get_option('ws_weibo_enable_history_today_option', false);


?>
    <div class="ws-wrap">
        <h2>API设置</h2>
        <form method="POST" action="">
            <?php wp_nonce_field('ws_weibo_api_settings_nonce'); ?>
            <h3>APIKey设置</h3>
            <label for="api_key">随机笑话API Key：</label>
            <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($current_api_key);?>" placeholder="请输入API Key">
            <br>

            <h3>热门视频API设置</h3>
            <label for="video_api_key">热门视频API Key：</label>
            <input type="text" name="video_api_key" id="video_api_key" value="<?php echo esc_attr($current_video_api_key);?>" placeholder="请输入热门视频API Key">
            <br>

            <h3>历史上的今天API设置</h3>
            <label for="history_today_api_key">历史上的今天API Key：</label>
            <input type="text" name="history_today_api_key" id="history_today_api_key" value="<?php echo esc_attr($current_history_today_api_key);?>" placeholder="请输入历史上的今天API Key">
            <br>

            <h3>功能选项</h3>
            <input type="checkbox" name="enable_jokes" id="enable_jokes" <?php checked($current_enable_jokes, true);?>>
            <label for="enable_jokes">启用随机笑话</label><br><br>

            <input type="checkbox" name="enable_videos" id="enable_videos" <?php checked($current_enable_videos, true);?>>
            <label for="enable_videos">启用热门视频</label><br><br>

            <input type="checkbox" name="enable_history_today" id="enable_history_today" <?php checked($current_enable_history_today, true);?>>
            <label for="enable_history_today">启用历史上的今天</label><br><br>

        
            <input type="submit" name="submit_api_settings" value="保存设置" class="button-primary">
        </form>
        <p>目前使用的聚合数据的api，会员制按年收费太贵了，我只是用来测试的。<br>如果你有稳定免费或者便宜的接口可以自己修改或者联系我添加。</p>
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


//API请求解析
function juheHttpRequesttest($url, $params = false, $isPost = 0) {
    $httpInfo = [];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    $response = curl_exec($ch);
    if ($response === FALSE) {
        return false;
    }
    curl_close($ch);
    return $response;
}

//随机笑话
function ws_weibo_get_random_joke($api_key) {
    $apiUrl = "http://v.juhe.cn/joke/randJoke"; // API请求URL
    $params = [
        'key' => $api_key,
    ];
    $paramsString = http_build_query($params);

    // 发起请求
    $response = juheHttpRequesttest($apiUrl, $paramsString, 1);

    if (!$response) {
        return null;
    }

    $result = json_decode($response, true);
    if ($result && isset($result['result'][0]['content'])) {
        return $result['result'][0]['content'];
    }

    return null;
}
//随机笑话结束

// 获取热门视频
function ws_weibo_get_hot_videos($api_key) {
    $apiUrl = "http://apis.juhe.cn/fapig/douyin/billboard"; // 接口请求URL
    $params = [
        'key' => $api_key,
        'type' => 'hot_video', // 热门视频的type参数
        'size' => '3', // 可选，定义每页返回的视频数量
    ];
    $paramsString = http_build_query($params);

    // 发起请求
    $response = juheHttpRequesttest($apiUrl, $paramsString, 1);

    if (!$response) {
        return null;
    }

    $result = json_decode($response, true);
    if ($result && isset($result['result'])) {
        return $result['result']; // 返回视频列表
    }

    return null;
}

//历史上的今天
function ws_weibo_get_random_history_today($api_key) {
    $apiUrl = "http://v.juhe.cn/todayOnhistory/queryEvent"; // 历史上的今天API请求URL
    $params = [
        'key' => $api_key,
        'date' => date('n/j'), // 当前日期，如：1/1
    ];
    $paramsString = http_build_query($params);

    // 发起请求
    $response = juheHttpRequesttest($apiUrl, $paramsString, 1);

    if (!$response) {
        return null;
    }

    $result = json_decode($response, true);
    if ($result && isset($result['result']) && is_array($result['result']) && count($result['result']) > 0) {
        // 随机选择一个事件
        $random_event = $result['result'][array_rand($result['result'])];
        return $random_event;
    }

    return null;
}

