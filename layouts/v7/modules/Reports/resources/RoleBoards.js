(function ($) {
	'use strict';

	var TONES = ['blue', 'emerald', 'amber', 'cyan', 'violet', 'rose'];

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
		});
	}

	function cardHtml(card, index) {
		var missing = card.missing || card.value === 'Chưa đủ dữ liệu';
		var tone = card.tone || TONES[index % TONES.length];
		return '<div class="mk-admin-kpi-card mk-admin-kpi-card--static' + (missing ? ' is-missing' : '') + '" data-tone="' + esc(tone) + '" role="listitem">'
			+ '<span class="mk-admin-kpi-card-label">' + esc(card.label) + '</span>'
			+ (missing
				? '<span class="mk-admin-kpi-card-empty">Chưa đủ dữ liệu</span>'
				: '<span class="mk-admin-kpi-card-value">' + esc(card.value) + '</span>')
			+ (card.hint ? '<span class="mk-admin-kpi-card-label">' + esc(card.hint) + '</span>' : '')
			+ '</div>';
	}

	function paint(boards) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		var keys = boards ? Object.keys(boards) : [];
		if (!keys.length) {
			box.innerHTML = '<div class="mk-admin-kpi-detail-loading">Chưa có báo cáo cho vai này.</div>';
			return;
		}
		box.__mkBoards = boards;
		box.__mkRole = keys.indexOf('ceo') >= 0 ? 'ceo' : keys[0];
		renderRole(box);
	}

	function renderRole(box) {
		var boards = box.__mkBoards || {};
		var keys = Object.keys(boards);
		var active = boards[box.__mkRole] ? box.__mkRole : keys[0];
		box.__mkRole = active;
		var board = boards[active] || {};
		var html = '';
		if (keys.length > 1) {
			html += '<div class="mk-role-switch" role="tablist">';
			keys.forEach(function (key) {
				var title = (boards[key] && boards[key].title) || key;
				html += '<button type="button" role="tab" data-mk-role="' + esc(key) + '"'
					+ (key === active ? ' class="is-on" aria-selected="true"' : ' aria-selected="false"')
					+ '>' + esc(title) + '</button>';
			});
			html += '</div>';
		}
		box.__mkGoods = board.goods || null;
		html += '<h3 class="mk-admin-kpi-subtitle">' + esc(board.title || active) + '</h3>';
		html += '<div class="mk-admin-kpi-grid" role="list">';
		(board.cards || []).forEach(function (card, index) {
			html += cardHtml(card, index);
		});
		html += '</div>';
		if (board.goods) {
			html += goodsHtml(board.goods);
		}
		box.innerHTML = html;
		bindRoleSwitch(box);
		bindGoods(box);
	}

	function bindRoleSwitch(box) {
		var sw = box.querySelector('.mk-role-switch');
		if (!sw) return;
		sw.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('[data-mk-role]') : null;
			if (!btn) return;
			var next = btn.getAttribute('data-mk-role');
			if (!next || next === box.__mkRole) return;
			box.__mkRole = next;
			renderRole(box);
		});
	}

	function fmtQty(n) {
		var v = Number(n) || 0;
		return v.toLocaleString('vi-VN', { maximumFractionDigits: v % 1 ? 1 : 0 });
	}

	function fmtMoney(n) {
		return Math.round(Number(n) || 0).toLocaleString('vi-VN');
	}

	function goodsTable(headers, rows, mapRow) {
		var html = '<table class="mk-goods-table"><thead><tr>';
		headers.forEach(function (h) {
			html += '<th' + (h.num ? ' class="is-num"' : '') + '>' + esc(h.label) + '</th>';
		});
		html += '</tr></thead><tbody>';
		if (!rows.length) {
			html += '<tr><td colspan="' + headers.length + '" class="mk-goods-empty">Chưa có số trong khoảng này.</td></tr>';
		} else {
			rows.forEach(function (row) {
				html += '<tr>' + mapRow(row) + '</tr>';
			});
		}
		html += '</tbody></table>';
		return html;
	}

	function goodsHtml(goods) {
		var stock = goods.stock || {};
		var alerts = goods.alerts || {};
		var html = '<section class="mk-goods-report" data-mk-goods="1">';
		html += '<h3 class="mk-admin-kpi-subtitle">Báo cáo hàng hóa</h3>';
		html += '<div class="mk-goods-summary">';
		html += summaryCard('Số mặt hàng còn tồn', fmtQty(stock.sku_count), 'Hàng đang có số lượng lớn hơn 0');
		html += summaryCard('Tổng kho', fmtQty(stock.qty), 'Tổng số lượng đang lưu');
		html += summaryCard('Giá trị tồn kho', fmtMoney(stock.value), 'Số lượng nhân giá nhập hoặc giá bán');
		html += '</div>';
		html += '<div class="mk-goods-alerts">';
		html += alertCard('Hết hàng hôm nay', alerts.out_today, 'rose');
		html += alertCard('Hết trong 7 ngày', alerts.out_7, 'amber');
		html += alertCard('Hết trong 30 ngày', alerts.out_30, 'slate');
		html += alertCard('Không bán được', alerts.unsold, 'rose');
		html += alertCard('Tồn cao hơn mức bán', alerts.overstock, 'violet');
		html += '</div>';
		html += '<p class="mk-goods-note">Cảnh báo lấy lượng bán 30 ngày gần nhất chia cho tồn hiện tại. Không bán được là hàng còn tồn nhưng không có đơn trong 30 ngày.</p>';

		html += '<div class="mk-goods-block">';
		html += '<div class="mk-goods-block__head"><h4>Doanh thu và lượng bán</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="period">';
		html += '<button type="button" class="is-on" data-mk-goods-period="day">Ngày</button>';
		html += '<button type="button" data-mk-goods-period="week">Tuần</button>';
		html += '<button type="button" data-mk-goods-period="month">Tháng</button>';
		html += '</div></div>';
		html += '<div data-mk-goods-period-table="1"></div>';
		html += '</div>';

		html += '<div class="mk-goods-split">';
		html += '<div class="mk-goods-block"><h4>Nhóm hàng theo tồn</h4>';
		html += goodsTable(
			[{ label: 'Nhóm' }, { label: 'Tồn kho', num: true }, { label: 'Giá trị', num: true }],
			goods.groups || [],
			function (row) {
				return '<td>' + esc(row.name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.value) + '</td>';
			}
		);
		html += '</div>';
		html += '<div class="mk-goods-block"><div class="mk-goods-block__head"><h4>Hàng theo tồn</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="stock">';
		html += '<button type="button" class="is-on" data-mk-goods-stock="qty">Theo số lượng</button>';
		html += '<button type="button" data-mk-goods-stock="value">Theo giá trị</button>';
		html += '</div></div><div data-mk-goods-stock-table="1"></div></div>';
		html += '</div>';

		html += '<div class="mk-goods-block">';
		html += '<div class="mk-goods-block__head"><h4>Bán chạy và bán chậm · 30 ngày</h4>';
		html += '<div class="mk-goods-switch" data-mk-goods-switch="rank">';
		html += '<button type="button" class="is-on" data-mk-goods-rank="fast">Bán chạy</button>';
		html += '<button type="button" data-mk-goods-rank="slow">Bán chậm</button>';
		html += '</div></div>';
		html += '<div data-mk-goods-rank-table="1"></div>';
		if (!goods.has_returns) {
			html += '<p class="mk-goods-note">Chưa có phiếu trả hàng, nên báo cáo không có cột số lượng trả.</p>';
		}
		if (!goods.has_cost) {
			html += '<p class="mk-goods-note">Chưa có giá vốn trên hàng hóa, nên chưa tính lợi nhuận gộp.</p>';
		}
		html += '</div></section>';
		return html;
	}

	function summaryCard(label, value, hint) {
		return '<div class="mk-goods-summary__card"><span>' + esc(label) + '</span><strong>' + esc(value) + '</strong><em>' + esc(hint) + '</em></div>';
	}

	function alertCard(label, value, tone) {
		return '<div class="mk-goods-alert" data-tone="' + esc(tone) + '"><span>' + esc(label) + '</span><strong>' + fmtQty(value) + '</strong></div>';
	}

	function periodRows(goods, grain) {
		var rows = (goods.periods && goods.periods[grain]) || [];
		return goodsTable(
			[{ label: grain === 'day' ? 'Ngày' : (grain === 'week' ? 'Tuần' : 'Tháng') }, { label: 'Lượng bán', num: true }, { label: 'Doanh thu', num: true }],
			rows,
			function (row) {
				return '<td>' + esc(row.label) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.revenue) + '</td>';
			}
		);
	}

	function stockRows(goods, mode) {
		var rows = mode === 'value' ? (goods.stock_top_value || []) : (goods.stock_top_qty || []);
		return goodsTable(
			[{ label: 'Tên hàng' }, { label: 'Tồn kho', num: true }, { label: 'Giá trị', num: true }],
			rows,
			function (row) {
				var name = row.name + (row.sku ? ' · ' + row.sku : '');
				return '<td>' + esc(name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.value) + '</td>';
			}
		);
	}

	function rankRows(goods, mode) {
		var rows = mode === 'slow' ? (goods.slow || []) : (goods.fast || []);
		var headers = [{ label: 'Tên hàng' }, { label: 'Số lượng bán', num: true }, { label: 'Doanh thu', num: true }];
		if (goods.has_cost) {
			headers.push({ label: 'Lợi nhuận gộp', num: true });
		}
		return goodsTable(headers, rows, function (row) {
			var name = row.name + (row.sku ? ' · ' + row.sku : '');
			var html = '<td>' + esc(name) + '</td><td class="is-num">' + fmtQty(row.qty) + '</td><td class="is-num">' + fmtMoney(row.revenue) + '</td>';
			if (goods.has_cost) {
				html += '<td class="is-num">' + fmtMoney(row.profit) + '</td>';
			}
			return html;
		});
	}

	function bindGoods(box) {
		var root = box.querySelector('[data-mk-goods="1"]');
		if (!root || !box.__mkGoods) return;
		var goods = box.__mkGoods;
		var period = 'day';
		var stockMode = 'qty';
		var rank = 'fast';
		var periodBox = root.querySelector('[data-mk-goods-period-table="1"]');
		var stockBox = root.querySelector('[data-mk-goods-stock-table="1"]');
		var rankBox = root.querySelector('[data-mk-goods-rank-table="1"]');
		function paintTables() {
			if (periodBox) periodBox.innerHTML = periodRows(goods, period);
			if (stockBox) stockBox.innerHTML = stockRows(goods, stockMode);
			if (rankBox) rankBox.innerHTML = rankRows(goods, rank);
		}
		paintTables();
		root.addEventListener('click', function (e) {
			var periodBtn = e.target.closest ? e.target.closest('[data-mk-goods-period]') : null;
			var stockBtn = e.target.closest ? e.target.closest('[data-mk-goods-stock]') : null;
			var rankBtn = e.target.closest ? e.target.closest('[data-mk-goods-rank]') : null;
			var btn = periodBtn || stockBtn || rankBtn;
			if (!btn) return;
			var group = btn.parentNode;
			if (group) {
				Array.prototype.forEach.call(group.querySelectorAll('button'), function (el) {
					el.classList.remove('is-on');
				});
			}
			btn.classList.add('is-on');
			if (periodBtn) period = periodBtn.getAttribute('data-mk-goods-period');
			if (stockBtn) stockMode = stockBtn.getAttribute('data-mk-goods-stock');
			if (rankBtn) rank = rankBtn.getAttribute('data-mk-goods-rank');
			paintTables();
		});
	}

	function fail(message) {
		var box = document.getElementById('mkRoleReportsBody');
		if (!box) return;
		box.innerHTML = '<div class="mk-admin-kpi-detail-error">' + esc(message || 'Không tải được báo cáo theo vai.') + '</div>';
	}

	function load() {
		if (!document.getElementById('mkRoleReportsBody')) return;
		$.ajax({
			url: 'index.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			data: { module: 'Home', action: 'AdminKpiApi', mode: 'role_boards' }
		}).done(function (res) {
			if (res && res.result && typeof res.result === 'object') {
				res = res.result;
			}
			if (!res || res.success === false) {
				var message = 'Không tải được báo cáo theo vai.';
				if (res && res.error) {
					message = typeof res.error === 'string' ? res.error : (res.error.message || message);
				}
				fail(message);
				return;
			}
			paint(res.boards || {});
		}).fail(function () {
			fail('Không tải được báo cáo theo vai.');
		});
	}

	$(load);
})(jQuery);
