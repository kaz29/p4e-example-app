# Useful commands

 * `npm run build`   compile typescript to js
 * `npm run watch`   watch for changes and compile
 * `cdk deploy`      deploy this stack to your default AWS account/region
 * `cdk diff`        compare deployed stack with current state
 * `cdk synth`       emits the synthesized CloudFormation template

## 環境設定

実行前に、`.env` ファイルを生成してください。

必要な項目は以下のとおりです。

- GITHUB_TOKEN => githubのアクセストークン
- HTPASSWD => htpasswdコマンドで作成される `user:password_hash` 形式の文字列

```
GITHUB_TOKEN=token
HTPASSWD=user:passwd_hash
```