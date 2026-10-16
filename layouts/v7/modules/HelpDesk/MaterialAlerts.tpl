{strip}
<div class="mk-tre-page mk-nl-page" lang="vi">
	<header class="mk-tre-hero">
		<div class="mk-tre-hero__copy">
			<p class="mk-tre-eyebrow">Tag Rule Engine · Hỗ trợ</p>
			<h1 class="mk-tre-title">Quản lý</h1>
		</div>
	</header>
	<div class="mk-tre-toolbar-bar">
		<div class="mk-tre-tabs" role="tablist">
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=rules">Rule</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=tags">Tag</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=scenarios">Kịch bản</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=affiliate">Mã giới thiệu</a>
			<a class="mk-tre-tab" href="index.php?module=HelpDesk&amp;view=Rules&amp;app=SUPPORT&amp;tab=questions">Câu hỏi</a>
			<a class="mk-tre-tab is-active" href="index.php?module=HelpDesk&amp;view=MaterialAlerts&amp;app=SUPPORT" aria-current="page">Cảnh báo nguyên liệu</a>
		</div>
	</div>

	<p class="mk-nl__sub">Điền số vào ô muốn bật. Ô để trống thì việc đó không báo. Bấm Lưu ngưỡng sau khi chỉnh. Việc đang mở và chỉ số một khách nằm ở menu Cảnh báo.</p>

	<form class="mk-nl__card" id="mk-nl-settings">
		<div class="mk-nl__card-head">
			<h2>Chọn khi nào thì báo</h2>
			<button type="submit">Lưu ngưỡng</button>
		</div>
		{assign var=NL_GROUP value=''}
		{foreach from=$NL_FIELDS item=FIELD}
			{if $FIELD.group neq $NL_GROUP}
				{if $NL_GROUP neq ''}</div></section>{/if}
				{assign var=NL_GROUP value=$FIELD.group}
				<section class="mk-nl__group">
					<h3>{$FIELD.group|escape}</h3>
					<div class="mk-nl__grid">
			{/if}
			<label class="mk-nl__field">
				<span>{$FIELD.label|escape}</span>
				<small>{$FIELD.hint|escape}</small>
				<input type="text" name="{$FIELD.key|escape}" value="{$NL_SETTINGS[$FIELD.key]|escape}" placeholder="{$FIELD.placeholder|escape}" />
			</label>
		{/foreach}
		{if $NL_GROUP neq ''}</div></section>{/if}
		<p class="mk-nl__msg" data-nl-msg hidden></p>
	</form>

</div>
<style>
.mk-nl-page { width: 100%; max-width: none; box-sizing: border-box; }
.mk-nl__sub { margin: 8px 0 18px; color: #64748b; font-size: 15px; }
.mk-nl__card { background: #fff; border: 1px solid #dde4ec; border-radius: 16px; padding: 18px 20px 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
.mk-nl__card h2 { margin: 0; font-size: 18px; }
.mk-nl__card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.mk-nl__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.mk-nl__field { display: flex; flex-direction: column; gap: 4px; min-width: 0; font-size: 14px; font-weight: 650; color: #0f172a; }
.mk-nl__group { margin-top: 8px; }
.mk-nl__group h3 { margin: 18px 0 10px; font-size: 15px; color: #0b6e4f; }
.mk-nl__field small { font-weight: 500; color: #64748b; letter-spacing: 0; line-height: 1.4; }
.mk-nl__field--id { max-width: 280px; }
.mk-nl input { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-weight: 500; background: #fff; }
.mk-nl__card-head button, .mk-nl__close button, .mk-nl__actions button, .mk-nl__lookup button { border: 0; border-radius: 10px; padding: 8px 14px; font-weight: 700; cursor: pointer; }
.mk-nl__card-head button, .mk-nl__close button, .mk-nl__lookup button { background: #08a045; color: #fff; }
.mk-nl__actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.mk-nl__actions button { background: #f1f5f9; color: #0f172a; }
.mk-nl__alert { border-top: 1px solid #e8edf3; padding: 14px 0; }
.mk-nl__alert header { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.mk-nl__alert em { font-style: normal; background: #ecfdf3; color: #0b6e4f; border-radius: 999px; padding: 2px 8px; font-size: 12px; font-weight: 700; }
.mk-nl__lookup { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.mk-nl__lookup button { height: 42px; }
.mk-nl-board { margin-top: 16px; }
.mk-nl-board__status { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.mk-nl-board__status div { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 14px; }
.mk-nl-board__status em { display: block; font-style: normal; font-size: 12px; font-weight: 700; color: #166534; letter-spacing: .04em; text-transform: uppercase; }
.mk-nl-board__status strong { display: block; margin-top: 4px; font-size: 16px; color: #14532d; }
.mk-nl-board__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.mk-nl-tile { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; min-width: 0; }
.mk-nl-tile small { display: block; font-size: 11px; font-weight: 800; color: #08a045; letter-spacing: .06em; }
.mk-nl-tile span { display: block; margin-top: 4px; font-size: 12px; color: #64748b; }
.mk-nl-tile strong { display: block; margin-top: 6px; font-size: 16px; color: #0f172a; word-break: break-word; }
.mk-nl-tile.is-empty strong { font-size: 13px; font-weight: 650; color: #94a3b8; }
.mk-nl__empty { color: #64748b; }
.mk-nl__msg { margin: 10px 0 0; color: #0b6e4f; font-weight: 700; }
.mk-nl__close { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; align-items: center; }
.mk-nl__close button { grid-column: 1 / -1; justify-self: start; }
@media (max-width: 800px) {
	.mk-nl__grid, .mk-nl__close, .mk-nl-board__grid, .mk-nl-board__status { grid-template-columns: 1fr; }
}
</style>
<script>
(function () {
	function post(data) {
		return app.request.post({ data: data });
	}
	var settings = document.getElementById("mk-nl-settings");
	if (settings) {
		settings.addEventListener("submit", function (e) {
			e.preventDefault();
			var payload = {};
			settings.querySelectorAll("input[name]").forEach(function (input) {
				payload[input.name] = input.value;
			});
			post({
				module: "HelpDesk",
				action: "MaterialAlertsApi",
				mode: "save_settings",
				payload: JSON.stringify(payload)
			}).then(function (err) {
				var msg = settings.querySelector("[data-nl-msg]");
				msg.hidden = false;
				msg.textContent = err ? "Không lưu được ngưỡng." : "Đã lưu ngưỡng.";
			});
		});
	}
})();
</script>
{/strip}
