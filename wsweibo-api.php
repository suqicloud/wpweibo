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
        $weather_api_key = sanitize_text_field($_POST['weather_api_key']);
        $enable_weather = isset($_POST['enable_weather']) ? true : false;
        $enable_bing_wallpaper = isset($_POST['enable_bing_wallpaper']) ? true : false;
        $enable_night_mode = isset($_POST['enable_night_mode']) ? true : false;
        $enable_clock = isset($_POST['enable_clock']) ? true : false;

        // 保存设置
        update_option('ws_weibo_api_key_option', $api_key);
        update_option('ws_weibo_enable_jokes_option', $enable_jokes);
        update_option('ws_weibo_video_api_key_option', $video_api_key);
        update_option('ws_weibo_enable_videos_option', $enable_videos);
        update_option('ws_weibo_history_today_api_key_option', $history_today_api_key);
        update_option('ws_weibo_enable_history_today_option', $enable_history_today);
        update_option('ws_weibo_weather_api_key_option', $weather_api_key);
        update_option('ws_weibo_enable_weather_option', $enable_weather);
        update_option('ws_weibo_enable_bing_wallpaper_option', $enable_bing_wallpaper);
        update_option('ws_weibo_enable_night_mode_option', $enable_night_mode);
        update_option('ws_weibo_enable_clock_option', $enable_clock);

        echo "<div class='updated'><p>API设置已更新。</p></div>";
    }

    // 获取当前API设置
    $current_api_key = get_option('ws_weibo_api_key_option', '');
    $current_enable_jokes = get_option('ws_weibo_enable_jokes_option', false);
    $current_video_api_key = get_option('ws_weibo_video_api_key_option', '');
    $current_enable_videos = get_option('ws_weibo_enable_videos_option', false);
    $current_history_today_api_key = get_option('ws_weibo_history_today_api_key_option', '');
    $current_enable_history_today = get_option('ws_weibo_enable_history_today_option', false);
    $current_weather_api_key = get_option('ws_weibo_weather_api_key_option', '');
    $current_enable_weather = get_option('ws_weibo_enable_weather_option', false);
    $current_enable_bing_wallpaper = get_option('ws_weibo_enable_bing_wallpaper_option', false);
    $current_enable_night_mode = get_option('ws_weibo_enable_night_mode_option', false);
    $current_enable_clock = get_option('ws_weibo_enable_clock_option', false);

?>
    <div class="ws-wrap">
        <h2>API接口设置</h2>
        <form method="POST" action="">
            <?php wp_nonce_field('ws_weibo_api_settings_nonce'); ?>
            <h3>聚合数据 - 随机笑话API设置</h3>
            <label for="api_key">随机笑话API Key：</label>
            <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($current_api_key);?>" placeholder="请输入API Key" style="width: 100%; max-width: 300px;">
            <br>

            <h3>聚合数据 - 热门视频API设置</h3>
            <label for="video_api_key">热门视频API Key：</label>
            <input type="text" name="video_api_key" id="video_api_key" value="<?php echo esc_attr($current_video_api_key);?>" placeholder="请输入热门视频API Key" style="width: 100%; max-width: 300px;">
            <br>

            <h3>聚合数据 - 历史上的今天API设置</h3>
            <label for="history_today_api_key">历史上的今天API Key：</label>
            <input type="text" name="history_today_api_key" id="history_today_api_key" value="<?php echo esc_attr($current_history_today_api_key);?>" placeholder="请输入历史上的今天API Key" style="width: 100%; max-width: 300px;">
            <br>

            <h3>心知天气Token设置</h3>
            <label for="weather_api_key">天气Token：</label>
            <input type="text" name="weather_api_key" id="weather_api_key" value="<?php echo esc_attr($current_weather_api_key);?>" placeholder="请输入天气API Key" style="width: 100%; max-width: 300px;">
            <br>

            <h3>功能选项</h3>
            <input type="checkbox" name="enable_jokes" id="enable_jokes" <?php checked($current_enable_jokes, true);?>>
            <label for="enable_jokes">启用随机笑话(左侧边栏)</label><br><br>

            <input type="checkbox" name="enable_videos" id="enable_videos" <?php checked($current_enable_videos, true);?>>
            <label for="enable_videos">启用热门视频(左侧边栏)</label><br><br>

            <input type="checkbox" name="enable_history_today" id="enable_history_today" <?php checked($current_enable_history_today, true);?>>
            <label for="enable_history_today">启用历史上的今天(左侧边栏)</label><br><br>

            <input type="checkbox" name="enable_weather" id="enable_weather" <?php checked($current_enable_weather, true);?>>
            <label for="enable_weather">启用天气显示(左侧边栏)</label><br><br>

            <input type="checkbox" name="enable_bing_wallpaper" id="enable_bing_wallpaper" <?php checked($current_enable_bing_wallpaper, true);?>>
            <label for="enable_bing_wallpaper">启用必应壁纸(右侧边栏)</label><br><br>

            <input type="checkbox" name="enable_clock" id="enable_clock" <?php checked($current_enable_clock, true); ?>>
            <label for="enable_clock">启用时钟效果（左侧边栏显示时钟）</label><br><br>

            <input type="checkbox" name="enable_night_mode" id="enable_night_mode" <?php checked($current_enable_night_mode, true);?>>
            <label for="enable_night_mode">启用夜间模式（右侧边栏显示切换开关，只对插件板块有效）</label><br><br>
            
            <input type="submit" name="submit_api_settings" value="保存设置" class="button-primary">
        </form>
        <p>目前使用的聚合数据的api，会员制按年收费太贵了，我只是用来测试的。<br>如果你有稳定免费或者便宜的接口可以自己修改或者联系我添加。<br>
        心知天气Token获取文档看这里：https://docs.seniverse.com/widget/start/get.html <br>
    注册账号 - 添加产品 - 配置插件 - 获取代码，里面就会出现一个token，把这个token设置到这里来就行。</p>
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

// 显示时钟
function ws_display_digital_clock() {
    // 获取时钟启用设置
    $enable_clock = get_option('ws_weibo_enable_clock_option', false);
    
    // 如果没有启用时钟，直接返回
    if (!$enable_clock) {
        return;
    }
    ?>
    <div class="digital-clock">
        <div class="time">
            <span class="hours">01</span>
            <span class="dots">:</span>
            <span class="minutes">01</span>
            <div class="right-side">
                <span class="period"></span>
                <span class="seconds">00</span>
            </div>
        </div>
        <div class="calender">
            <span class="year"></span>年
            <span class="month-name"></span>
            <span class="day-num"></span>日
            <span class="day-name"></span>
        </div>
    </div>
    <style>
        .digital-clock {
            position: relative;
            color: #fff;
            background: #2e2e44;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06);
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        @keyframes glowing {
            0% {
                background-position: 0 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0 50%;
            }
        }

        .time {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hours,
        .dots,
        .minutes {
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            padding: 0 10px;
        }

        .hours,
        .minutes {
            font-size: 1.5em;
        }

        .dots {
            color: #929292;
        }

        .hours {
            background: -webkit-linear-gradient(90deg, #634dff, #5fd4ff);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
        }

        .minutes {
            background: -webkit-linear-gradient(90deg, #ff5e9e, #ffb960);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
        }

        .right-side {
            position: relative;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-left: 10px;
        }

        .period,
        .seconds {
            font-size: 0.75em;
            font-weight: 500;
        }

        .period {
            transform: translateY(-20px);
            background: -webkit-linear-gradient(90deg, #f7b63f, #faf879);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
        }

        .seconds {
            transform: translateY(16px);
            background: -webkit-linear-gradient(90deg, #24ff6d, #2f93f1);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
        }

        .calender {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 5px;
            background: -webkit-linear-gradient(90deg, #ae4af6, #ff98d1);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
        }

        .day-name,
        .day-num,
        .year {
            margin-left: 8px;
        }
    </style>

    <script>
        function updateTime() {
            let today = new Date();
            let hours = today.getHours();
            let minutes = today.getMinutes();
            let seconds = today.getSeconds();
            let period = "AM";

            if (hours >= 12) { period = "PM"; }
            if (hours < 10) { hours = "0" + hours; }
            if (minutes < 10) { minutes = "0" + minutes; }
            if (seconds < 10) { seconds = "0" + seconds; }

            document.querySelector(".hours").innerHTML = hours;
            document.querySelector(".minutes").innerHTML = minutes;
            document.querySelector(".period").innerHTML = period;
            document.querySelector(".seconds").innerHTML = seconds;

            const dayNum = today.getDate();
            const year = today.getFullYear();
            const dayName = today.toLocaleString("default", { weekday: "long" });
            const monthName = today.toLocaleString("default", { month: "short" });
            document.querySelector(".year").innerHTML = year;
            document.querySelector(".month-name").innerHTML = monthName;
            document.querySelector(".day-name").innerHTML = dayName;
            document.querySelector(".day-num").innerHTML = dayNum;
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
    <?php
}

// 夜间模式
function ws_weibo_night_mode() {
    $enable_night_mode = get_option('ws_weibo_enable_night_mode_option', false);

    // 如果夜间模式启用，显示夜间模式开关
    if ($enable_night_mode) {
        ?>
        <div class="ws_night_mode_toggle">
            <label class="ws_switch">
                <input type="checkbox" id="ws_night_mode_toggle" <?php echo (isset($_COOKIE['night_mode']) && $_COOKIE['night_mode'] == 'enabled') ? 'checked' : ''; ?>>
                <span class="ws_slider"></span>
            </label>
        </div>
        <script>
            document.getElementById('ws_night_mode_toggle').addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('ws_night_mode');
                    document.cookie = 'night_mode=enabled; path=/; max-age=' + 60*60*24*365; // 存储夜间模式状态
                } else {
                    document.body.classList.remove('ws_night_mode');
                    document.cookie = 'night_mode=disabled; path=/; max-age=' + 60*60*24*365; // 移除夜间模式状态
                }
            });

            // 页面加载时检查夜间模式状态
            if (document.cookie.indexOf('night_mode=enabled') !== -1) {
                document.body.classList.add('ws_night_mode');
                document.getElementById('ws_night_mode_toggle').checked = true;
            }
        </script>
        <style>
            .ws_night_mode {
                background-color: #121212;
                color: #999;
            }

            /* 需要在夜间模式下更改的区域 */
            .ws_night_mode .ws-container,
            .ws_night_mode .ws-feeling,
            .ws_night_mode .ws-statistics,
            .ws_night_mode .ws-site-owner,
            .ws_night_mode .ws-weather,
            .ws_night_mode .ws-random-articles,
            .ws_night_mode .ws-comment-section,
            .ws_night_mode .ws-feeling-sidebar,
            .ws_night_mode .ws-history-today,
            .ws_night_mode .ws-hot-videos,
            .ws_night_mode .ws-random-joke,
            .ws_night_mode .ws-feeling-left-sidebar,
            .ws_night_mode .ws-announcement,
            .ws_night_mode form textarea {
                background-color: #1c1c1c;
            }

            /* 夜间模式开关样式 */
            .ws_night_mode_toggle {
                position: relative;
                margin-bottom: 10px;
            }

            .ws_switch {
                position: relative;
                display: inline-block;
                width: 34px;
                height: 20px;
            }

            .ws_switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .ws_slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: 0.4s;
                border-radius: 50px;
            }

            .ws_slider:before {
                position: absolute;
                content: "🌞";  /* 默认显示太阳图标 */
                font-size: 16px;
                height: 16px;
                width: 16px;
                border-radius: 50%;
                bottom: 2px;
                background-color: transparent;
                text-align: center;
                line-height: 16px;
                transition: 0.4s;
            }

            input:checked + .ws_slider {
                background-color: #2196F3;
            }

            input:checked + .ws_slider:before {
                transform: translateX(10px);
                content: "🌙";  /* 滑块选中时显示月亮图标 */
            }

            /* 去掉原始圆点 */
            .ws_slider:after {
                display: none;
            }
        </style>
        <?php
    }
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

// 获取必应壁纸
function get_bing_wallpaper() {
    $json_string = file_get_contents('https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=5&mkt=zh-CN');
    $wallpapers = json_decode($json_string, true);

    // 从获取的壁纸列表中随机选择一张壁纸
    if (isset($wallpapers['images']) && count($wallpapers['images']) > 0) {
        $random_wallpaper = $wallpapers['images'][array_rand($wallpapers['images'])];
        return 'https://cn.bing.com' . $random_wallpaper['url'];
    }
    return null; // 如果没有壁纸返回null
}
