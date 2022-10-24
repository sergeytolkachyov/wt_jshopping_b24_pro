document.addEventListener('DOMContentLoaded', function () {
	let wt_jshopping_b24_pro_options = Joomla.getOptions('wt_jshopping_b24_pro');
	const b24_product_list_modal = document.getElementById("bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id);
	b24_product_list_modal.addEventListener('shown.bs.modal', event => {
		let start = 0;
		getBitrix24ProductList(start);
	})


	function bindB24ProductButtons() {
		let b24_product_buttons = document.querySelectorAll('[data-b24-product-id]');
		b24_product_buttons.forEach(function (button, index, array) {
			button.addEventListener('click', function (event) {
				fillB24ProductIdField(button.getAttribute('data-b24-product-id'));
				window.Joomla.Modal.getCurrent().close();
			})

		})


		let field_b24_product_pagination_buttons = document.querySelectorAll('[data-b24-product-list-start]');
		field_b24_product_pagination_buttons.forEach(function (button, index, array) {
			button.addEventListener('click', function (event) {

				let start = button.getAttribute('data-b24-product-list-start');
				getBitrix24ProductList(start);


			})

		})

	}

	function fillB24ProductIdField(b24ProductId) {
		let wt_jshopping_b24_pro_options = Joomla.getOptions('wt_jshopping_b24_pro');
		let b24_product_id_input_field = document.getElementById(wt_jshopping_b24_pro_options.modal_id);
		console.log("b24ProductId.b24ProductId = " + b24ProductId);
		b24_product_id_input_field.value = b24ProductId;
	}

	function getBitrix24ProductList(start) {
		start = parseInt(start);
		Joomla.request({
			url: 'index.php?option=com_ajax&plugin=wt_jshopping_b24_pro&group=system&action=getBitrix24Products&format=json&start=' + start,
			onSuccess: function (response, xhr) {
				// Тут делаем что-то с результатами
				// Проверяем пришли ли ответы
				if (response !== '') {

					let result = JSON.parse(response);
					console.group('WT JSHopping Bitrix 24 PRO');
					console.log(result);
					console.groupEnd();
					if (result.success === true) {

						if (result.data[0].error_description) {
							var modal_body = document.querySelector("#bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id + " .modal-body");
							modal_body.innerHTML = '<div class="alert alert-danger"><h4>' + result.data[0].error + '</h4><p>' + result.data[0].error_description + '</p></div>';
							return false;
						}
						// Убеждаемся, что браузер поддерживает тег <template>,
						// проверив наличие атрибута content у элемента template.
						if ('content' in document.createElement('template')) {

							// Находим элемент tbody таблицы
							// и шаблон строки
							var tbody = document.querySelector("#bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id + "_product_table tbody");
							tbody.innerHTML = '';
							var template = document.querySelector("#bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id + "_productrow");

							result.data[0].result.products.forEach(function (item, index, array) {
								// Клонируем новую строку и вставляем её в таблицу
								var clone = template.content.cloneNode(true);
								var td = clone.querySelectorAll("td");
								// td[0].innerHTML = "<img src='" + item.PREVIEW_PICTURE + "' width='64px'>";
								td[0].innerHTML = item.name + " (ID: " + item.id + ")";
								td[1].innerHTML = "<button type='button' class='btn btn-primary btn-sm' data-b24-product-id='" + item.id + "'>Выбрать</button>";

								tbody.appendChild(clone);
							});


							// // Клонируем новую строку ещё раз и вставляем её в таблицу

							var pagination_container = document.querySelector("#bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id + "_product_pagination");

							// var template_pagination_button = document.querySelector("#bitrix24_products_field_" + wt_jshopping_b24_pro_options.modal_id + "_product_pagination");
							let b24_pagination_buttons = Math.ceil(parseInt(result.data[0].total) / 50);


							pagination_container.innerHTML = '';
							for (let i = 0; i < b24_pagination_buttons; i++) {
								var pagination_counter = i;
								var pagination_button = document.createElement('button');
								pagination_button.classList.add('btn', 'm-0');
								pagination_button.setAttribute('data-b24-product-list-start', (i === 0 ? 0 : pagination_counter * 50));
								if (start === (pagination_counter * 50)) {
									pagination_button.classList.add('bg-primary', 'text-white');
								}
								pagination_button.setAttribute('type', 'button');
								pagination_button.innerText = pagination_counter + 1;
								pagination_container.appendChild(pagination_button);
							}


						}//if ('content' in document.createElement('template'))

						bindB24ProductButtons();

					}
				}
			}
		});

	}// getBitrix24ProductList END
});