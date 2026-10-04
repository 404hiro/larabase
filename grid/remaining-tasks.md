# Remaining Implementation Tasks

- [x] 1. `/@:slug` のグリッドを元実装どおり `grid-layout-plus` に戻す。
- [x] 2. 編集時のドラッグ移動を実装する。
- [x] 3. PC/SP別レイアウトの移動・保存を実装する。
- [x] 4. ウィジェットのリサイズUIを実装する。
- [x] 5. `LinkWidgetControls` 相当の詳細操作パネルを移植する。
- [x] 6. 画像ウィジェットのトリミングUIを実装する。
- [x] 7. 画像ウィジェットへのリンク設定モーダルを実装する。
- [x] 8. テキストウィジェットの本文編集をカード上で直接できるようにする。
- [x] 9. テキストウィジェットの背景色、横揃え、縦揃え設定を実装する。
- [x] 10. リンクウィジェットのタイトル編集をカード上で直接できるようにする。
- [x] 11. YouTube/Vimeo/TikTokなどの埋め込み表示モードを再現する。
- [x] 12. センシティブリンク設定の編集UIを実装する。
- [x] 13. 地図ウィジェットの検索、位置変更、ズーム変更を実装する。
- [x] 14. スマホ編集時のBottom Sheet群を移植する。
- [x] 15. スマホ編集時の選択中ウィジェット操作UIを再現する。
- [x] 16. プロフィール画像のアップロード、削除、圧縮処理を実装する。
- [x] 17. BIO/表示名の `contenteditable` 編集挙動を元実装に寄せる。
- [x] 18. 公開ボタン、公開後の紙吹雪アニメーションを実装する。
- [x] 19. シェアコピーの表示挙動を元実装に寄せる。
- [x] 20. `fetch-ogp` 結果を使ったリンクカード生成を元実装同等にする。
- [x] 21. OGP画像アップロード/差し替えを実装する。
- [x] 22. ウィジェット最大数、URL長、タイトル長、テキスト長の制限を元実装どおりにする。
- [x] 23. 追加直後・レイアウト切替時・ドラッグ時のアニメーションを再現する。
- [x] 24. 空状態プレースホルダーの配置ロジックを元実装同等にする。
- [x] 25. `has_web_display` による訪問者PC/SP強制表示の挙動を細かく確認する。
- [x] 26. `/@:slug/message` はリンクだけ残すか、メッセージ画面/APIも移植するか決める。
- [x] 27. Go API側の認可・バリデーションをLaravel実装相当に強化する。
- [x] 28. Cloud Run向けの本番Dockerfile/環境変数/静的配信構成を固める。
- [x] 29. DB migration運用を `docker-entrypoint-initdb` ではなく専用migrationにする。
- [x] 30. 既存Laravel DBからGo版DBへのデータ移行スクリプトを作る。
- [x] 31. E2Eまたは最低限の画面確認テストを追加する。

## Additional Parity Fixes

- [x] 32. 画像ウィジェットのクロップをスライダー式からCropper.jsのドラッグ式に戻す。
- [x] 33. クロップ中の全画面オーバーレイ、対象ウィジェットの最前面表示、Cropperの枠/マスクCSSを元実装に寄せる。
- [x] 34. クロップ中はグリッドドラッグを無効化して、画像移動操作とウィジェット移動操作が競合しないようにする。
- [x] 35. ウィジェット移動中の浮き、傾き、影、grab/grabbingカーソル、クリック誤爆抑制を元実装に寄せる。
- [x] 36. ドラッグ中は他ウィジェットのpointer eventとテキスト選択を抑制する。

## Reopened Parity Tasks

- [x] 37. 画像ウィジェットのクロップUIを `resources/js/components/links/LinkWidgetContent.vue` と同じDOM構造に寄せる。現在の移植版はCropper.jsを載せただけで、元実装の `is-cropping-active`、画像本体、キャプション、リンクアイコン、Cropper overlay の重なり順が一致していない。
- [x] 38. PCクロップ時の操作開始/終了を `LinkWidgetControls.vue` と同じ挙動にする。元実装はCropボタン自体が「トリミング/完了」を兼ね、クロップ中はresizeボタンをdisabledにする。移植版は周辺アクションとの状態制御が不完全。
- [x] 39. スマホ画像クロップをグリッド上ではなく、元実装の `mobileImageEditorWidget` Bottom Sheet 内プレビューで行う。現在の移植版はスマホでもカード上Cropperになっており、元実装の大きめプレビュー、画像変更、キャプション、リンク、センシティブ設定と分離できていない。
- [x] 40. スマホ画像編集Sheetのクロッププレビュー寸法を `mobileImageEditorPreviewStyle` / `mobileImageEditorCropStyle` と同等にする。元実装はモバイルgrid寸法からプレビュー幅/高さを算出して、実カード比率を維持している。
- [x] 41. Cropper.jsの初期化処理をPC用とスマホSheet用で分ける。元実装には通常カード用 `cropper` とスマホSheet用 `mobileCropper` が別々にあり、それぞれ対象DOM、ready処理、destroyタイミングが異なる。
- [x] 42. クロップ中にウィジェットタイトル編集、リンクアイコン表示、hoverキャプション表示が出ないようにする。元実装では `hasImageLink && !isCropping` や `isCropping` overlay で明確に分岐している。
- [x] 43. 画像クロップ時の保存値計算を元実装どおり `canvasData.left / xRange`、`canvasData.top / yRange` ベースに統一し、カード表示時の `objectPosition` と完全対応させる。
- [x] 44. WidgetControlsを元の `LinkWidgetControls.vue` 構成に戻す。現在の移植版には元実装にない即時センシティブ切替の目アイコン、リンクウィジェットの画像差替ボタン、簡易地図UIなどが混ざっている。
- [x] 45. リンク/画像/テキストのセンシティブ設定は、元実装どおり `LockKeyhole` ボタンから `showContentLabels` ポップアップを開き、その中のswitchで切り替える。
- [x] 46. テキストウィジェットの色変更UIを元実装どおり、`Circle` ボタンからカラーパレット/カラーコード入力ポップアップを開く形にする。現在の移植版は常時ボタンが並びすぎている。
- [x] 47. テキストウィジェットの横/縦揃えボタン群を元実装どおり別段の黒いコントロールバーに分離し、active状態の白背景表示も合わせる。
- [x] 48. 埋め込みモード切替を元実装どおり、サイズバーとは別の2段目コントロールに分離する。現在の移植版はリンク操作列に混在していて見た目と位置が違う。
- [x] 49. `lockOpen` / `lockedControlsWidgetId` 相当を実装し、ポップアップを開いている間にhover外れでコントロールが閉じないようにする。
- [x] 50. onClickOutside相当を実装し、色/センシティブ/地図検索ポップアップを外側クリックで閉じる。現在の移植版は閉じ方と状態リセットが元実装と違う。
- [x] 51. 地図ウィジェットをiframeではなくLeaflet実装に戻す。元実装は `LinkWidgetContent.vue` 内でLeafletを動的importし、中心マーカー、ドラッグ有効/無効、ズーム同期を持っている。
- [x] 52. 地図操作は検索とズームだけでなく、元実装どおり `Move` ボタンによる地図移動モードを実装する。移動モード中だけLeaflet dragging/doubleClickZoomを有効にする。
- [x] 53. 地図移動中の中心更新を `update-map-center` イベントで保存する。現在の移植版は検索結果選択とズームのみで、手で地図中心をずらす操作がない。
- [x] 54. 地図ウィジェットの表示を元実装どおり、中心マーカー、pulse/dot、編集時タイトルtextarea、閲覧時タイトルpillにする。現在の移植版はiframe下部カード表示で差分が大きい。
- [x] 55. ウィジェット移動時の挙動を `resources/js/pages/links/Link.vue` の `dragPointerState` / `dragVisualState` / `motion-v` spring相当に寄せる。現在の移植版は軽量な見た目だけで、元の速度連動rotate、spring、settleの質感が再現できていない。
- [x] 56. `motion-v` の `useMotionValue` / `useSpring` を導入するか、同等のspring補間を自前実装して、ドラッグ中のtranslate/rotate/scale/shadowを元実装並みにする。
- [x] 57. ドラッグ開始閾値、ドラッグ終了後300msのクリック抑制、`isSuppressingWidgetInteractions`、`shouldSuppressWidgetClick` を元実装と同じ条件に揃える。
- [x] 58. PC/SPグリッド切替時の `isPreviewLayoutSwitching` と `disableLayoutTransitionsBriefly` を移植し、切替直後だけ不要なlayout transitionを止める。
- [x] 59. リサイズ時の衝突解決を元実装の `pushCollidingItems` と同じにする。現在の移植版は単純にサイズ変更後 `updateLayoutsFromWidgets` しており、重なりを下へ押し出す挙動が欠けている。
- [x] 60. 空状態プレースホルダーを固定配置ではなく、元実装の `desktopPlaceholderItems` / `mobilePlaceholderItems` と同じく既存ウィジェットとの衝突を避けて算出する。
- [x] 61. モバイル時は `drag-allow-from=".mobile-widget-move-handle"` にし、カード全体ドラッグではなく元実装と同じ黒い移動ハンドルからのみ移動できるようにする。
- [x] 62. モバイル選択中ウィジェットのUIを元実装どおり、左上削除、右上編集、下部移動ハンドルに分ける。現在の移植版は汎用Bottom Sheetにアクションが混ざっている。
- [x] 63. `LinkToolbar.vue` 相当のツールバーへ戻す。元実装はPC toolbar、モバイル toolbar、スタイルパネル、モバイルサイズ操作バーが分かれており、現在の移植版は単一の丸型バーに詰め込みすぎている。
- [x] 64. 元実装にない「場所検索はカード上の虫眼鏡」などの説明的/暫定的なアクション表示を削除する。
- [x] 65. `LinkProfile.vue` を移植または同等化し、プロフィール画像hover overlay、削除コントロール、contenteditable同期、paste plain text、focus中DOM同期抑制を再現する。
- [x] 66. `LinkWidgetContent.vue` のリンクカード表示差分を洗い直す。元実装のsocial/action service判定、App Store/Google Play/Amazon/Rakuten/支援系/ファンサイト系の色とaction pillが未移植。
- [x] 67. リンクカードのinline/1x1/2x1/1x2/2x2ごとのDOM、favicon失敗時フォールバック、OGP画像表示、編集時contenteditableタイトルを元実装に合わせる。
- [x] 68. テキストウィジェットをtextareaではなく元実装のcontenteditableに戻し、`beforeinput` / paste制限 / focused class / placeholder疑似要素を再現する。
- [x] 69. セクションウィジェットのサイズ選択肢をPC/SPで元実装の `sectionSizeOptions` と同じにする。現在は汎用sizeOptionsのfilterで代用している。
- [x] 70. 未保存変更の検知、beforeunload、画面遷移前confirmを移植する。現在の移植版は保存前に離脱できてしまう。
- [x] 71. 公開/保存/シェア/編集の状態遷移を元実装の `isLinkPublished`、`isPublishingLink`、`copiedProfileUrl`、toolbar表示条件に合わせる。
- [x] 72. モバイル追加フローを元実装どおり、リンク追加Sheet、テキスト追加Sheet、セクション追加Sheetに分離し、追加確定前の仮ウィジェットcommit/rollbackを実装する。
- [x] 73. モバイル編集Sheetをリンク、画像、地図、テキスト、セクションごとに分離する。現在の移植版の単一Bottom Sheetでは元の操作導線と一致しない。
- [x] 74. 元実装の `compressImage` preset `avatar` / `card` と同等の圧縮ロジックへ差し替える。現在の移植版は簡易canvas圧縮で設定差分がある。
- [x] 75. 追加直後アニメーションだけでなく、hover、active、toolbar popup、Sheet遷移、confettiを元実装のduration/easing/class構成に合わせる。
- [x] 76. ウィジェット移動中の背景/空きセル/プレースホルダー/ドラッグ対象以外の見え方を元実装と比較し、背景色・透明度・z-index・pointer-events・transitionを一致させる。現在の移植版はドラッグ中の背景色と周辺要素の抑制表現が元実装と違う。

## Mobile Viewport Parity Recheck

- [x] 77. モバイル表示時にウィジェット移動ハンドルが効かない原因を修正する。移植版は `drag-ignore-from` に `button` を含めていたため、`mobile-widget-move-handle` の `<button>` からドラッグ開始できなかった。
- [x] 78. モバイルGridの `drag-allow-from` を元実装どおり、実スマホ幅では `.mobile-widget-move-handle`、PC幅のSPプレビューでは `undefined` に切り替える。
- [x] 79. モバイルGridの `drag-ignore-from` を元実装どおり `.mobile-widget-ignore-drag, .widget-text-input--focused, a, input, textarea` に戻し、移動ハンドルを無視対象から外す。
- [x] 80. Bottom Sheetの表示条件を `activeMode === 'mobile'` ではなく、元実装と同じ実画面幅 `max-width: 1024px` 判定に寄せる。
- [x] 81. PC幅でSPプレビューしている時はモバイルBottom Sheetを開かず、カード上の `WidgetControls` を表示する挙動へ戻す。
- [x] 82. 実スマホ幅からPC幅へ切り替わった時に、開いているモバイル追加/編集Sheetを閉じる。
- [x] 83. モバイル選択中ウィジェットの左上削除、右上編集、下部移動ハンドルは実スマホ幅の時だけ表示する。
- [x] 84. 元実装の `LinkToolbar.vue` と同じく、モバイル操作バーは `mobileWidgetOperationActive = isSmallViewport && isEditing && activeMobileLayoutItem !== null` の時だけ通常モバイルToolbarと差し替える形へさらに整理する。
- [x] 85. PC幅SPプレビュー時の `ProfileWidget` の `is-editing` を元実装どおり `isEditing && !isSmallViewport` に合わせ、実スマホ幅ではカード上の直接編集を抑止する。
- [ ] 86. 実スマホ幅でカードタップ、編集ボタン、移動ハンドル、サイズ操作バー、Bottom Sheetの表示/非表示が元実装と同じ順序で切り替わるか手動確認する。
- [ ] 87. PC幅でSPプレビュー中にhover/lock中の `WidgetControls`、ドラッグ開始、クロップ、地図移動が元実装と同じか手動確認する。
- [ ] 88. 画面幅を1024px境界で跨いだ時に、active widget、crop state、map moving state、toolbar stateが破綻しないか確認する。
- [x] 89. 編集モードではないプレビュー/閲覧時のセクションウィジェットは背景と枠を出さず、文字だけ表示する。
- [x] 90. 編集/プレビュー切り替え、Web/SP切り替え、スマホ選択時の通常Toolbar/サイズ変更バー切り替えで、ウィジェットやプロフィールが移動/変形アニメーションせずパキッと切り替わるようにする。
- [x] 91. テキストウィジェットの上下中央/上/下揃えを、閲覧時だけでなく編集時のcontenteditableにも反映する。
- [x] 92. 実スマホ幅ではPC/SP切り替えボタンを表示せず、スマホ表示だけに固定する。
- [x] 93. 保存時にウィジェットIDを作り直さない同期方式へ変更し、右側に置いたウィジェットが保存後に移動/再アニメーションしないようにする。
- [x] 94. 保存時の `has_web_display` を元実装どおり実画面幅ベースで保存し、スマホ幅ではスマホ表示として扱う。
- [x] 95. 保存後に `loadProfile()` でGrid全体を再取得/再マウントせず、sync結果の新規IDだけローカル反映して既存ウィジェットの位置を保つ。
- [x] 96. プロフィール画像の変更/削除ボタンを画像の下部ではなく上部に表示する。
- [x] 97. 地図タイルをGoogle標準地図タイル `lyrs=m` に差し替える。
- [x] 98. 地図ウィジェットの背景、中心マーカー、タイトルpillは元実装の `LinkWidgetContent.vue` と同じ構成を維持する。

## Link/Image Widget Parity Recheck

- [x] 99. リンクウィジェットを元実装どおり `inline / 1x1 / 2x1 / 1x2 / 2x2` の形状別DOMに分離し、現在の汎用1カラム表示を廃止する。
- [x] 100. リンクウィジェットのinline表示を元実装どおり、左favicon、中央タイトル、余白のみの横並びにする。
- [x] 101. リンクウィジェットの1x1表示を元実装どおり、favicon、タイトル、下部domain/action pillの縦構成にする。
- [x] 102. リンクウィジェットの2x1/1x2/2x2表示で、OGP画像/埋め込み/画像差し替えボタン/削除ボタンの位置とhover表示を元実装に合わせる。
- [x] 103. リンクタイトル編集時の `widget-text-input--focused`、高さ、背景、grab/cursor挙動を元実装の `linkTitleEditorClasses` と同じにする。
- [x] 104. ソーシャル/アクションサービスの背景色、フォロー/購入/プレイpill、domain fallbackの表示順を元実装に合わせる。
- [x] 105. 画像ウィジェットのリサイズバーを元実装どおり、黒背景のサイズバー、サイズアイコン、クロップ中disabled、PC/SP位置に合わせる。
- [x] 106. 画像ウィジェットのリサイズ対象サイズを元実装どおり `small/wide/tall/large` のみにし、inlineを除外したままPC/SPグリッドへ正しく反映する。

## Bug Fixes

- [x] 107. リンクウィジェットのサイズ変更で形状別DOMが切り替わった後も、contenteditableのタイトルテキストを再同期して空にならないようにする。
- [x] 108. 画像ウィジェットのリサイズ時にcrop/map操作状態を閉じ、サイズボタンを押せる状態に戻し、Cropperの半透明レイヤーがリサイズ操作を阻害しないようにする。
- [x] 109. Crop中の半透明ページオーバーレイを画像ウィジェットより背面に下げ、Crop対象ウィジェットがオーバーレイの手前で操作できるようにする。
- [x] 110. 編集モード中は画像ウィジェット右上のリンクアイコンを表示しない。
- [x] 111. 編集モード中はメッセージボタンをdisabled表示にしてクリックできないようにする。
- [x] 112. Cropperのドラッグ面を元実装どおり操作可能に戻し、画像ドラッグで切り抜き位置を調整できるようにする。
- [x] 113. スマホ表示のCrop中はモバイル選択オーバーレイを出さず、Cropperを直接操作できるようにする。
- [x] 114. プロフィール画像の変更/削除UIを元実装に寄せ、画像全体クリックで変更、hoverオーバーレイ、左上削除ボタンにする。
- [x] 115. 実スマホ幅では直接編集を抑制するため `isEditing` がfalseになるケースでも、ページが編集モードなら画像ウィジェット右上のリンクアイコンを非表示にする。
- [x] 116. YouTubeチャンネルURLのリンク追加時に、通常OGPに加えて `channelMetadataRenderer` からチャンネル名とアバター画像を取得する。
- [x] 117. リンクウィジェットの背景色を外側カードに持たせ、内側の重複角丸を外してウィジェット角の隙間をなくす。
