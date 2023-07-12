# WordPress on EC2 with CFn and Ansible
## 概要と構成図
<img src="https://github.com/mo-Shu/WordPress_on_EC2/blob/main/architect_230712.png" width="500x">
- EC2上にWordPressを展開するフルシステムの設計に挑戦してみました。
- 一部を除いて（構築手順参照）、ほとんどをIaCで実現。クラウドインフラのリソース構築はCloudFormation、それ以外のLinuxで操作する部分をAnsible。
- SSM State ManagerによるAnsible Playbookの実行：少しチャレンジングな試みとして、Amazon Linux 2のAMIで起動した素の状態のEC2に対して、Ansible Playbookから設定する仕組みを作ってみました。※メリットやデメリットがわかってきたので後に整理。
- セキュアかつレスポンス速度向上のためCDNを導入：CloudFront + S3 + ALB + HTTPS化 + WAFのよくある構成です。想定していない経路からのアクセスを制御できるように頑張りました。
- 

## 構築手順（最終更新日：2023年7月12日）
### 事前にAWS上で構築されていると想定しているもの
1. ACMで{ドメイン名}および{*.ドメイン名}の証明書以下2つ用意
    - CloudFront用の証明書（us-east-1リージョンに構築。ドメイン認証まで完了）
    - ALB用の証明書（EC2をはじめメインのリソースを構築するリージョンに構築。ドメイン認証まで完了）※ap-northeast-1で動作確認済み
2. S3を用意して、Ansbile Playbook（wp_setup_pbまるごと）をzip形式で圧縮して保存。
3. 番号順にCFnテンプレートを実行する。※04-WAFはus-east-1
4. Session ManagerでEC2に接続し、DBに接続後、wordpress用のユーザーを作成する

```
# 実行例
# RDS_EP: 書き込み可能なAuroraエンドポイント
# Aurora マスターユーザー: root
# DB名: wordpressdb, DBユーザー: wordpress, DBパスワード: password

# DBにログイン
mysql -h $RDS_EP -u root -p

# mysqlで実行
CREATE USER 'wordpress'@'%' IDENTIFIED BY 'bQ6PbKZ2u9NO';
GRANT ALL ON wordpressdb.* TO 'wordpress'@'%';  
FLUSH PRIVILEGES;  
exit
```
5. 管理画面アクセスしてプラグイン
6. wp-config.phpに以下を追記
```php: wp-config.php
/** SSL via CloudFront **/ 
if($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';
        $_ENV['HTTPS'] = 'on';
}

/** HyperDB config file **/ 
define('DB_CONFIG_FILE', ABSPATH . 'db-config.php');

/** WP Offload Media Lite AWS Instance Role **/ 
define( 'AS3CF_SETTINGS', serialize( array( 
        'provider' => 'aws', 
        'use-server-roles' => true, 
) ) );

```
7.  各プラグイン（HyperDB、W3 Total、WP OffloadMediaを想定）のインストール・有効化・設定して完了