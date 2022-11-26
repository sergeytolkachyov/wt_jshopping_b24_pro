document.addEventListener('DOMContentLoaded', function () {
	let wt_jshopping_b24_pro_options = Joomla.getOptions('wt_jshopping_b24_pro');
	const b24_product_variations_list_modal = document.getElementById("bitrix24_products_variations_modal");
	b24_product_variations_list_modal.addEventListener('shown.bs.modal', event => {
		window.jshop_product_id = event.relatedTarget.getAttribute('data-product-id');
		window.jshop_product_attr_id = event.relatedTarget.getAttribute('data-product-attr-id');
		let start = 0;
		getBitrix24ProductVariationsList(start,wt_jshopping_b24_pro_options.product_parent_id_for_b24);
	})


	function bindB24ProductButtons() {
		// кнопки из <template>
		let b24_product_variations_buttons = document.querySelectorAll('[data-b24-product-variation-select-btn]');
		b24_product_variations_buttons.forEach(function (button, index, array) {
			button.addEventListener('click', function (event) {
				fillB24ProductVariationIdField(window.jshop_product_id,window.jshop_product_attr_id, button.getAttribute('data-b24-product-variation-select-btn'));
				window.Joomla.Modal.getCurrent().close();
			})

		})


		let field_b24_product_pagination_buttons = document.querySelectorAll('[data-b24-product-list-start]');
		field_b24_product_pagination_buttons.forEach(function (button, index, array) {
			button.addEventListener('click', function (event) {

				let start = button.getAttribute('data-b24-product-list-start');
				getBitrix24ProductVariationsList(start);

			})

		})

	}

	function fillB24ProductVariationIdField(jshop_product_id,jshop_product_attr_id,b24ProductVariationId) {
		let wt_jshopping_b24_pro_options = Joomla.getOptions('wt_jshopping_b24_pro');
		let b24_product_variation_id_input_field = document.getElementById("b24-product-variation-"+jshop_product_id+"-"+jshop_product_attr_id);
		b24_product_variation_id_input_field.value = b24ProductVariationId;
	}

	function getBitrix24ProductVariationsList(start,product_parent_id_for_b24) {
		start = parseInt(start);
		Joomla.request({
			url: 'index.php?option=com_ajax&plugin=wt_jshopping_b24_pro&group=system&action=getBitrix24ProductsVariations&format=json&b24_parent_product_id='+product_parent_id_for_b24+'&start=' + start,
			onSuccess: function (response, xhr) {
				// Тут делаем что-то с результатами
				// Проверяем, пришли ли ответы
				if (response !== '') {

					let result = JSON.parse(response);
					console.group('WT JSHopping Bitrix 24 PRO - product variations');
					console.log(result);
					console.groupEnd();
					if (result.success === true) {

						if (result.data[0].error_description) {
							var modal_body = document.querySelector("#bitrix24_products_variations_modal .modal-body");
							modal_body.innerHTML = '<div class="alert alert-danger"><h4>' + result.data[0].error + '</h4><p>' + result.data[0].error_description + '</p></div>';
							return false;
						}
						// Убеждаемся, что браузер поддерживает тег <template>,
						// проверив наличие атрибута content у элемента template.
						if ('content' in document.createElement('template')) {

							// Находим элемент tbody таблицы
							// и шаблон строки
							var tbody = document.querySelector("#bitrix24_products_variations_modal_product_table tbody");
							tbody.innerHTML = '';
							var template = document.querySelector("#bitrix24_products_variations_modal_productrow");

							result.data[0].result.products.forEach(function (item, index, array) {
								// Клонируем новую строку и вставляем её в таблицу
								var clone = template.content.cloneNode(true);
								var td = clone.querySelectorAll("td");
								// td[0].innerHTML = "<img src='" + item.PREVIEW_PICTURE + "' width='64px'>";
								td[0].innerHTML = item.name + " (ID: " + item.id + ")";
								td[1].innerHTML = "<button type='button' class='btn btn-primary btn-sm' data-b24-product-variation-select-btn='" + item.id + "'>Выбрать</button>";

								tbody.appendChild(clone);
							});


							// // Клонируем новую строку ещё раз и вставляем её в таблицу

							var pagination_container = document.querySelector("#bitrix24_products_variations_modal_product_pagination");

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

	}// getBitrix24ProductVariationsList END
});