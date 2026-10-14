{* Chuyển kho giữa các kho — localStorage prototype *}
{strip}
<div class="mk-gi-page">
	<section class="mk-wh-mgmt">
		<header class="mk-wh-proto-head">
			<div class="mk-wh-proto-title">
				<div class="mk-wh-mgmt-breadcrumb">
					<a href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">← Danh sách kho</a>
				</div>
				<h1 class="mk-wh-proto-title__h1">Chuyển kho giữa các kho</h1>
				<p class="mk-wh-proto-title__sub">Tạo phiếu xuất chuyển kho giữa các kho. Phiếu được lưu vào kho nguồn, tab Xuất kho.</p>
			</div>
			<div class="mk-wh-mgmt-toolbar">
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhList&amp;app=INVENTORY">Danh sách kho</a>
				<a class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" href="index.php?module=Warehouse&amp;view=WhDashboard&amp;app=INVENTORY">Bảng điều khiển kho</a>
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary" id="mkWhTransferCreateBtn">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					Tạo phiếu xuất chuyển kho
				</button>
			</div>
		</header>

		<div class="mk-wh-mgmt-panel" aria-label="Phiếu chuyển kho">
			<div class="mk-wh-mgmt-panel__head">
				<h2 class="mk-wh-mgmt-panel__title">Phiếu chuyển kho</h2>
			</div>
			<div class="mk-wh-mgmt-table-wrap">
				<table class="mk-wh-mgmt-table" role="table">
					<thead>
						<tr>
							<th>Mã</th>
							<th>Từ kho</th>
							<th></th>
							<th>Đến kho</th>
							<th>SKU / Lô</th>
							<th class="mk-wh-mgmt-td-right">SL</th>
							<th>Lý do</th>
							<th>Trạng thái</th>
							<th class="mk-wh-mgmt-td-right">Thao tác</th>
						</tr>
					</thead>
					<tbody id="mkWhTransferTableBody"></tbody>
				</table>
			</div>
		</div>
	</section>
</div>

<div class="mk-wh-mgmt-modal hide" id="mkWhTransferFormModal" role="dialog" aria-modal="true" aria-labelledby="mkWhTransferFormTitle">
	<div class="mk-wh-mgmt-modal__backdrop" data-mk-wh-trf-close="1"></div>
	<div class="mk-wh-mgmt-modal__panel mk-wh-mgmt-modal__panel--wide mk-wh-trf-modal">
		<header class="mk-wh-mgmt-modal__head">
			<h3 id="mkWhTransferFormTitle">Tạo phiếu xuất — Chuyển kho</h3>
			<button type="button" class="mk-wh-mgmt-modal__close" data-mk-wh-trf-close="1" aria-label="Đóng">&times;</button>
		</header>
		<form id="mkWhTransferForm" class="mk-wh-mgmt-form mk-wh-trf-form">
			<input type="hidden" id="mkWhTrfUser" value="{$MK_WH_USER_NAME|escape:'html'}" />
			<div class="mk-wh-mgmt-form-grid">
				<label class="mk-wh-mgmt-field"><span>Kho nguồn *</span>
					<select name="from" id="mkWhTrfFrom" required><option value="">Chọn kho nguồn</option></select>
				</label>
				<label class="mk-wh-mgmt-field"><span>Kho đích *</span>
					<select name="to" id="mkWhTrfTo" required><option value="">Chọn kho đích</option></select>
				</label>
				<label class="mk-wh-mgmt-field mk-wh-mgmt-field--full"><span>Mã chuyển kho</span>
					<input type="text" name="so" id="mkWhTrfCode" placeholder="TRF-..." autocomplete="off" />
				</label>
			</div>
			<div class="mk-wh-proto-field mk-wh-proto-field--full mk-wh-proto-field--lines">
				<div class="mk-wh-proto-lines-section">
					<div class="mk-wh-proto-lines-section__head">
						<div class="mk-wh-proto-lines-section__titles">
							<span class="mk-wh-proto-lines-section__label">Danh sách hàng xuất</span>
							<span class="mk-wh-proto-lines-section__hint">Tìm hàng trong kho nguồn — lô, đơn vị và hạn dùng lấy theo tồn kho</span>
						</div>
					</div>
					<div class="mk-wh-proto-lines mk-wh-proto-lines--catalog mk-wh-proto-lines--outbound" data-mk-lines="1">
						<div class="mk-wh-proto-lines__quick" role="search">
							<label class="mk-wh-proto-lines__quick-label" for="mkWhTrfSearch">Tìm hàng hoá / SKU</label>
							<select id="mkWhTrfSearch" disabled="disabled"><option value="">Chọn kho nguồn trước</option></select>
						</div>
						<div class="mk-wh-proto-lines__tableWrap">
							<table class="mk-wh-proto-lines__table" role="table">
								<thead>
									<tr>
										<th class="mk-wh-proto-col-name">Tên hàng</th>
										<th class="mk-wh-proto-col-sku">SKU</th>
										<th class="mk-wh-proto-col-unit">Đơn vị</th>
										<th class="mk-wh-proto-col-lot">Lô *</th>
										<th class="mk-wh-proto-col-qty">SL *</th>
										<th class="mk-wh-proto-col-date">NSX</th>
										<th class="mk-wh-proto-col-date">HSD *</th>
										<th class="mk-wh-proto-col-act"></th>
									</tr>
								</thead>
								<tbody id="mkWhTrfLines"></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<footer class="mk-wh-mgmt-modal__foot">
				<button type="button" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--outline" data-mk-wh-trf-close="1">Hủy</button>
				<button type="submit" class="mk-wh-mgmt-btn mk-wh-mgmt-btn--primary">Tạo phiếu</button>
			</footer>
		</form>
	</div>
</div>
{/strip}
