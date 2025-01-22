我的初衷是不想每一次都进后台去发布，比如我空白回声主题这种，然后就是希望网站的用户偶尔发个心情啥的。
如果你要后台只能管理员的，可以看我主题里面的代码，是一个独立的文件，改成插件就行。  

插件名称：小半微心情

<mark style="background-color: red; color: white;">插件是基于Modown主题开发调试的，不太兼容其他主题，其他主题使用需要自己优化。</mark>

插件页面如果要设置为网站首页，就要关闭其他其他用户组发微博的权限。  


插件功能：  
1.普通用户发布微博(登录用户，包括订阅者和贡献者权限，WordPress默认这2个是不能发布文章的)    
2.可以设置发布微博的用户组权限  
3.用户封禁(被封用户不能发布微博和评论)  
4.管理员可以删除某个用户的全部微博/评论、或者删除全站微博/评论  
5.关键词屏蔽(添加一些关键词，如果前台用户的内容提到关键词就自动显示为*号)  
6.网址白名单(白名单的域名支持自动解析图片或视频)  
7.图片和视频链接自动解析(网址白名单，把图床的域名加进白名单，粘贴图片链接就会直接显示为图片）  
8.图片支持：jpg、jpeg、png、gif、bmp、webp（支持9宫格模式）  
9.视频支持：mp4、webm  
10.本站点地址自动加链接标签（如果是外部网址就不加）  
11.限制发布微博的时间段  
12.点击前台微博用户名会访问用户的author页面  
13.前台的微博支持点赞  
14.前台的微博允许他人评论  
15.前台允许用户删除自己的单条微博记录或者删除自己的全部微博记录  
16.隐藏前台微博页面用户统计板块  
17.隐藏前台微博页面评论功能  
18.自定义前台页面微博每一页的显示数量  
19.可选前台微博页面的滑动/固定模式  
20.自定义微博列表板块的css  
21.支持3栏模式（需要添加左右侧边栏，只添加一个侧边栏，建议添加右侧边栏，）  
22.侧边栏上面自定义内容，支持html代码  
23.左边侧边栏支持对接api数据，需要到后台填写你自己的聚合数据apikey  
24.聚合数据目前支持：随机笑话、历史上的今天、热门视频  
25.可以当留言板使用(设置用户组权限，这样他们就只能发评论)  
26.支持自定义前台页面标题  
27.支持用户上传图片(默认限制2MB大小以内的图片，jpg、png、gif、webp格式)  
28.支持加载b站视频  
29.支持加载网页云音乐（免费音乐）  
30.左侧边栏可以随机显示5篇网站文章。  



社区反馈：https://iticu.icu/forum  



插件启用会自动创建前台微博页面，如果没有自动创建可以手动，短代码：
`[ws_weibo_feeling]`

  
关于api：  
我需要一个稳定并且能免费测试的api接口，所以选择了聚合数据来测试，只是他们免费用户只能对接3个接口，收费的按年卖，最便宜一年都要几千块，实在是太贵了！他们应该是没考虑个人开发者，如果你有免费稳定或者便宜的接口可以联系我添加测试。  

插件会创建2个独立的数据表：ws_weibo_feelings（微博数据）、ws_weibo_comments（微博评论），如果你以后彻底不用这个插件了，可以去删掉这2个数据表。


![截图](https://ice.frostsky.com/2024/12/21/a9576e11520c230a9fc3ad50aec2b7fa.jpeg)
