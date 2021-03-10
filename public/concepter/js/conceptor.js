// при загрузке новой страницы в первую очередь очищаем локальное хранилище
localStorage.clear();

(function($) {
    $.fn.textfill = function(options) {
        var fontSize = options.maxFontPixels;
        var ourText = $('span:visible:first', this);
        var maxHeight = $(this).height();
        var maxWidth = $(this).width();
        var textHeight;
        var textWidth;
        do {
            ourText.css('font-size', fontSize);
            textHeight = ourText.height();
            textWidth = ourText.width();
            fontSize = fontSize - 1;
        } while ((textHeight > maxHeight || textWidth > maxWidth) && fontSize > 3);
        return this;
    }
})(jQuery);

function storeSize(e, $el, opt) {
			var h = $el.attr('id') + '_h';
			var w = $el.attr('id') + '_w';
			localStorage.setItem(h, $el.css('height').split('p')[0]);
			localStorage.setItem(w, $el.css('width').split('p')[0]);
		}
		
function storePlace(ev,obj){
			if(obj['el'] != undefined){
				obj = $(obj['el']);
			}
			var l = obj.attr('id') + '_' + 'l';
			var t = obj.attr('id') + '_' + 't';
			//console.log(obj['el']);
			localStorage.setItem(l, obj.position().left);
			localStorage.setItem(t, obj.position().top);
		}

//Целая часть от деления
function div(val, by){
    return (val - val % by) / by;
}

function calcBotom(objs){
    var dist = 0;
    var two_borders;
    for (var obj in objs){
        two_borders = Number.parseInt(objs[obj]['bw']) ? Number.parseInt(objs[obj]['bw']) : 0;
        dist = Math.max(dist, Number.parseInt(objs[obj]['t']) + Number.parseInt(objs[obj]['h']) + two_borders * 2);
    }
    return dist;
}

function pasteBlocks(objs){
    
    for (var obj in objs){
        //console.log(objs[obj]);
        //console.log(obj.split('-')[0]);
        objs[obj]['id'] = obj;
        switch (obj.split('-')[0]) {
          case 'block':
            pasteBlock(null, objs[obj]);
            break;
          case 'textb':
            pasteText(null, objs[obj]);
            break;
          case 'imb':
            pasteImg(null, objs[obj]);
            break;
            /*
          default:
            alert( "Нет таких значений" );
            */
        }
    }
    
    var bottom = calcBotom(objs);
    //console.log(bottom);
    //console.log($('#container').height());
    if(bottom > $('#container').height()){
        $('#container').height(bottom);
    }
    
}

function offsCalc(jbl){
	var scroll = $(window).scrollTop();
	var topo = div(scroll + 320, 32) * 32 + 1;
	var lefto = conLeft() + 385;
	return {left: lefto, top: topo};
}

function storeText(pe){
	var block_id = pe.attr('id'),
	text = pe.children('.etxt').html();
	localStorage.setItem(block_id + '_tx', text.replace(/\s/ig, '&nbsp;'));
};

function zMax(el){
	var parent = el.closest('.lay');
	localStorage.setItem('lastz', parent.css('zIndex'));
	parent.css({zIndex: 2000});
};

function zNorm(el){
	var parent = el.closest('.lay');
	parent.css({zIndex: localStorage.getItem('lastz')});
	localStorage.removeItem('lastz');
};

function calcTotalZ(){
	var bls = $('#container').children('.lay');
	
	bls.each(function(i){
		var el = $(this);
		var z = Math.abs(($('#container').width() - Math.max(el.width(), el.height()))|0) + 2;
		el.css({zIndex: z});
	});
	
	//console.log(bls);
}

function conLeft(){
	return $('#container').offset().left;
}

function nCalc(){
	if(localStorage.getItem('n') == null){
	localStorage.setItem('n', 1);
	}
	return localStorage.getItem('n');
}

function bottomResize(ev,obj){
	if(obj.$el != undefined){
		obj = obj.$el;
	}
	var objmax = obj.height() + obj.offset().top;
	var con = $('#container');
	var conmax = con.height();
	var vh = $(window).height();
	if(objmax > vh && objmax > conmax){
		con.height(objmax);
	}
}

function calcPop(pel){
	var pq = $(pel),
		pleft = pq.offset().left,
		ptop = pq.offset().top,
		con = $('#container'),
		conl = con.offset().left,
		conr = con.offset().left + con.width(),
		wind = $(window).height(),
		res = '';
	if(pq.hasClass('colwheel')){
		if((conr - pleft) < 300){
			res = 'left';
		}else{
			res = 'right';
		}
	}else{
		if((pleft - conl) < 300){
			res = 'right';
		}else{
			res = 'left';
		}
	}
	return res;
}

function pasteBlock(e, obj){
	var n, id, hsel, style, offs;
	if(obj != undefined){
		style = {zIndex: Math.abs(($('#container').width() - Math.max(obj['w'], obj['h']))|0) + 2, width: obj['w'], height: obj['h'], backgroundColor: obj['c'], borderColor: obj['bc'], borderWidth: obj['bw'], borderRadius: obj['br'] + 'px'};
	}else{
		style = {zIndex: 768};
	}
	n = nCalc();
	id = 'block-' + n;
	hsel = '>.b' + n + 'h';
	
	var block = $('<div class="lay notxt" id="' + id + '"><div class="ntbx x iface"></div><div class="corn-size b' + n + 'h iface"></div><div class="colwheel iface"></div><div class="bord iface"></div>').pep({grid:[16,16], drag: function(e, obj){
		bottomResize(e, obj);
		picker.settings.popup = calcPop(wheel);
		}, stop: storePlace}).resizable({ handleSelector: hsel, onDrag: function(e, obj){
		bottomResize(e, obj);
		picker.settings.popup = calcPop(wheel);
		}, onDragEnd: storeSize});
	$('#container').append(block);
	
	n++;
	localStorage.setItem('n', n);
	
	var jbl = $('#' + id);
	if(obj != undefined){
		offs = {left: conLeft() + Number.parseInt(obj['l']) + 1, top: Number.parseInt(obj['t']) + 1};
	}else{
		offs = offsCalc(jbl);
	}
	jbl.offset(offs);
	
	jbl.css(style);
	localStorage.setItem(id + '_c', jbl.css('backgroundColor').split('p')[0]);
	localStorage.setItem(id + '_bw', jbl.css('borderTopWidth').split('p')[0]);
	localStorage.setItem(id + '_br', jbl.css('borderTopLeftRadius').split('p')[0]);
	localStorage.setItem(id + '_bc', jbl.css('borderTopColor').split('p')[0]);
	
	storeSize(null, jbl, null);
	storePlace(null, jbl);
	
	var initc = block.css("backgroundColor");
	var wheel = block.children('.colwheel')[0];
	
	var picker = new Picker({parent: wheel, color:initc, onOpen: function(){
		if($('#iface').is(':checked')){
			zMax($(this.settings.parent));
		}
		//this.settings.popup = 'left';
	}});
	picker.onChange = function(color) {
		var block = this.settings.parent.parentElement;
		block.style.backgroundColor = color.rgbaString();
	};
	picker.onClose = function(color){
		localStorage.setItem(picker.settings.parent.parentElement.id + '_c',color.rgbaString());
		if($('#iface').is(':checked')){
			zNorm($(this.settings.parent));
		}
		delete this;
	};
	
	var bord = block.children('.bord');
	bord.popover({
		title : 'Границы блока',//title,string
		content : function(){
			var par = $(this.ele).parent('.lay');
			return '<div class="popx"></div><p>Толщина <input id="borw" type="number" value="' + par.css('borderTopWidth').split('p')[0] +'"></p><p>Радиус <input id="borr" type="number" value="' + par.css('borderTopLeftRadius').split('p')[0] + '"></p><p>Цвет <span data-ntbl="' + par.attr('id') + '" class="bwheel"></span></p>';
		},//content,string,function,object
		autoPlace : true,//Set a reasonable place,default true
		trigger : 'click',//Trigger mode,default 'hover','click','focus'
		placement : 'right',//Preferred placement, if autoPlace is false,Fixed here
		delay : 100, //Show and hide delay time
		afterOpen : function(){
			var wheel = $('.bwheel');
			var initc = $('#' + wheel.data('ntbl')).css('borderTopColor');
			//console.log(initc);
			var picker = new Picker({parent: wheel[0], popup: calcPop(wheel), color: initc, onChange: function(color){
				var block = $('#' + $(this.settings.parent).data('ntbl'));
				block.css({borderColor: color.rgbaString()});
			}, onClose: function(color){
				var block_id = $(this.settings.parent).data('ntbl');
				localStorage.setItem(block_id + '_bc',color.rgbaString());
			}});
		},
		closeSelector: '.popx',
		beforeClose : function(){
			var block_id = $('.bwheel').data('ntbl');
			if(block_id != undefined){
			var block = $('#' + block_id);
				localStorage.setItem(block.attr('id') + '_bw', block.css('borderTopWidth').split('p')[0]);
				localStorage.setItem(block.attr('id') + '_br', block.css('borderTopLeftRadius').split('p')[0]);
			}
		}
	});
}

function pasteText(e, obj){
	var n, id, hsel, style, offs;
	if(obj != undefined){
		style = {zIndex: Math.abs(($('#container').width() - Math.max(obj['w'], obj['h']))|0) + 2, width: obj['w'], height: obj['h']};
	}else{
		style = {zIndex: 768};
	}
	n = nCalc();
	id = 'textb-' + n;
	hsel = '>.b' + n + 'h';
	var text = $('<div class="lay txt" id="' + id + '"><div class="twheel iface"></div><div class="tbx x iface"></div><span class="etxt" contenteditable="true">Новый текст</span><div class="corn-size b' + n + 'h iface"></div></div>').pep({grid:[1,1], stop: storePlace, drag: function(e, obj){
		bottomResize(e, obj);
		picker.settings.popup = calcPop(wheel);
		}}).resizable({ handleSelector: hsel, onDrag: function(e, $el, newWidth, newHeight, opt){
		bottomResize(e, $el);
		$el.textfill({ maxFontPixels: 150 });
		}, onDragStart(e, $el, opt){
			$el.css({border: '1px dashed red'});
		}, onDragEnd(e, $el, opt){
			$el.css({border: 'none'});
			storeSize(e, $el, opt);
		}
	}).css({zIndex: 768});
	$('#container').append(text);
	
	n++;
	localStorage.setItem('n', n);
	
	var jbl = $('#' + id);
	if(obj != undefined){
		offs = {left: conLeft() + Number.parseInt(obj['l']) + 1, top: Number.parseInt(obj['t']) + 1};
		jbl.children('.etxt').css({color: obj['tc']}).html(obj['tx']);
	}else{
		offs = offsCalc(jbl);
	}
	jbl.offset(offs);
	
	jbl.css(style).textfill({ maxFontPixels: 150 });
	localStorage.setItem(id + '_tc', jbl.children('.etxt').css('color'));
	
	storeSize(null, jbl, null);
	storePlace(null, jbl);
	storeText(jbl);
	
	var initc = text.children('span').css("color");
	var wheel = text.children('.twheel')[0];
	var picker = new Picker({parent: wheel, popup: 'left', color: initc, onOpen: function(){
		if($('#iface').is(':checked')){
			zMax($(this.settings.parent));
		}
	}});
	picker.onChange = function(color) {
		this.settings.parent.nextSibling.nextSibling.style.color = color.rgbaString();
	};
	picker.onClose = function(color){
		localStorage.setItem(this.settings.parent.parentElement.id + '_tc', color.rgbaString());
		if($('#iface').is(':checked')){
			zNorm($(this.settings.parent));
		}
	};
}

function pasteImg(e, obj){
	var n, id, hsel, style, offs;
	if(obj != undefined){
		style = {zIndex: Math.abs(($('#container').width() - Math.max(obj['w'], obj['h']))|0 + 2), width: obj['w'], height: obj['h'], backgroundColor: obj['c']};
	}else{
		style = {zIndex: 768};
	}
	n = nCalc();
	id = 'imb-' + n;
	hsel = '>.b' + n + 'h';
	var block = $('<div class="lay anyimg" id="' + id + '"><div class="pbx x iface"></div><div class="corn-size b' + n + 'h iface"></div><div class="colwheel iface"></div></div>').pep({grid:[1,1], drag: function(e, obj){
		picker.settings.popup = calcPop(wheel);
		}, stop: storePlace}).resizable({ handleSelector: hsel, onDrag: function(e, obj){
		picker.settings.popup = calcPop(wheel);
		}, onDragEnd: storeSize});
	$('#container').append(block);
	
	n++;
	localStorage.setItem('n', n);
	
	var jbl = $('#' + id);
	if(obj != undefined){
		offs = {left: conLeft() + Number.parseInt(obj['l']) + 1, top: Number.parseInt(obj['t']) + 1};
	}else{
		offs = offsCalc(jbl);
	}
	jbl.offset(offs);
	
	jbl.css(style);
	localStorage.setItem(id + '_c', jbl.css('backgroundColor').split('p')[0]);
	
	storeSize(null, jbl, null);
	storePlace(null, jbl);
	
	var initc = block.css("backgroundColor");
	n++;
	localStorage.setItem('n', n);
	var wheel = block.children('.colwheel')[0];
	
	var picker = new Picker({parent: wheel, color:initc, onOpen: function(){
		if($('#iface').is(':checked')){
			zMax($(this.settings.parent));
		}
	}});
	picker.onChange = function(color) {
		var block = this.settings.parent.parentElement;
		block.style.backgroundColor = color.rgbaString();
	};
	picker.onClose = function(color){
		localStorage.setItem(picker.settings.parent.parentElement.id + '_c',color.rgbaString());
		if($('#iface').is(':checked')){
			zNorm($(this.settings.parent));
		}
	};
}

$('#container').resizable({ handleSelector: ".conhandle", resizeWidth: false});

	$('#ntb').click(function(e){
		pasteBlock(e);
	});
	
	$('body').on('change keyup', '#borw', function(e){
		var block = $(e.target).parentsUntil('.popover-content').siblings('p').find('.bwheel').data('ntbl');
		block = $('#' + block);
		block.css({borderWidth: $(e.target).val() + 'px'});
	}).on('change keyup', '#borr', function(e){
		var block = $(e.target).parentsUntil('.popover-content').siblings('p').find('.bwheel').data('ntbl');
		block = $('#' + block);
		block.css({borderRadius: $(e.target).val() + 'px'});
	});
	
	$('#tb').click(function(e){
		pasteText(e);
	});
	
	// Сохраняем текст при сведении мыши или потери фокуса
	$('#container').on('mouseleave blur', '.etxt', function(e){
		var pe = $(this).parents('.txt');
		storeText(pe);
	});
	
	$('#container').on('input', '.etxt', function(e){
		var pe = $(this).parents('.txt');
		pe.textfill({ maxFontPixels: 150 });
	});
	
	$('#pb').click(function(e){
		pasteImg(e);
	});
	
	
	$('#container').on('mouseenter', '.txt>div', function(e){
		var el = $(e.currentTarget);		
			el.parent('.txt').addClass('btransp');
			el.siblings('span').addClass('btransp');
	}).on('mouseleave', '.txt>div', function(e){
		var el = $(e.currentTarget);		
			el.parent('.txt').removeClass('btransp');
			el.siblings('span').removeClass('btransp');
	});
	$('#container').on("mouseenter mouseleave", '.lay>div,span', function(){
      $.pep.toggleAll();
    });
	
	for(var i=0; i<localStorage.length; i++) {
		var key = localStorage.key(i);
		//console.log(`${key}: ${localStorage.getItem(key)}`);
	}

	$('#iface').click(function() {
		if ($(this).is(':checked')) {
			$('.iface').css({display: 'block'});
		} else {
			$('.iface').css({display: 'none'});
		}  
	});
	
	
	$('body').on('click', '.lay', function(e){
		var div = $(e.target);
		if($('#iface').is(':not(:checked)') && div.closest('.lay') !== null){
			//Сначала скрываем все элементы интерфейса на всякий случай
			$('.iface').css({display: 'none'});
			calcTotalZ();
			//console.log('Скрываем все!');
			if(div.children('.iface').css('display') != 'block'){
				if(div.children('.iface').length != 0){
					div.children('.iface').css({display: 'block'});
					if(div.css('zIndex') !== 2000){
						zMax(div);
					}
					//console.log('Показываем 1!');
				}else if(div.hasClass('etxt')){
					div.closest('.lay').children('.iface').css({display: 'block'});
					if(div.css('zIndex') !== 2000){
						zMax(div.closest('.lay'));
					}
				}else{
					div.closest('.iface').css({display: 'block'});
					if(div.css('zIndex') !== 2000){
						zMax(div.closest('.lay'));
					}
					//console.log('Показываем 2!');
				}
			}
		}
			
	});
	
	$('body').on('click', function(e){
		if($('#iface').is(':not(:checked)')){
			if(e.target.closest('.lay') === null && e.target.closest('.popover') === null){
				$('.iface').css({display: 'none'});
				calcTotalZ();
				//console.log('Скрываем!');
			}
		}
	});
	
	$('#setka').click(function() {
		if ($(this).is(':checked')) {
			$('#container').css({background: 'linear-gradient( #bbb, transparent 1px), linear-gradient( 90deg, #bbb, white 1px)', backgroundSize: '32px 32px',	backgroundPosition: 'left top'});
		} else {
			$('#container').css({background: 'white', backgroundSize: 'none',	backgroundPosition: 'none'});
		}  
	});
	
	$('#razdel').click(function() {
		if ($(this).is(':checked')) {
			$('.razm').css({display: 'block'});
		} else {
			$('.razm').css({display: 'none'});
		}  
	});
	
$(document).keyup(function(e){
	if(e.keyCode === 45){
		$('#aside').toggle();
	}
	/*
	for(var i=0; i<localStorage.length; i++) {
		var key = localStorage.key(i);
		console.log(`${key}: ${localStorage.getItem(key)}`);
	}
	*/
});

$('.addb').on('click', function(e){
	e.preventDefault();
});

$('#clears').click(function(){
	localStorage.clear();
});

function download(content, fileName, contentType) {
    var a = document.createElement("a");
    var file = new Blob([content], {type: contentType});
    a.href = URL.createObjectURL(file);
    a.download = fileName;
    a.click();
}

$('#gjsn').click(function(){
	var jobj = new Object();
	for(var i=0; i<localStorage.length; i++) {
		var key = localStorage.key(i);
		if(key.indexOf('_') > -1){
			var val = localStorage.getItem(key);
			var kparts0 = key.split('_')[0];
			var kparts1 = key.split('_')[1];
			if(jobj[kparts0] === undefined){
				jobj[kparts0] = new Object();
			}
			jobj[kparts0][kparts1] = val;
		}
	}
	//$('#mod2>textarea').val(JSON.stringify(jobj));
    //var blob = new Blob([JSON.stringify(jobj)], {type: "application/json"});
    download(JSON.stringify(jobj), 'concept.json', 'application/json');

});

$('#jsnsub').on('click',{id: 'fileinput'}, function(event){
    
    function loadFileFromInput() {
        var input, file, fr;

        if (typeof window.FileReader !== 'function') {
          alert("The file API isn't supported on this browser yet.");
          return;
        }

        input = document.getElementById(event.data.id);
        if (!input) {
          alert("Um, couldn't find the fileinput element.");
        }
        else if (!input.files) {
          alert("This browser doesn't seem to support the `files` property of file inputs.");
        }
        else if (!input.files[0]) {
          alert("Please select a file before clicking 'Load'");
        }
        else {
          file = input.files[0];
          console.log(file);
          fr = new FileReader();
          fr.onload = receivedText;
          fr.readAsText(file);
        }

        function receivedText(e) {
          let lines = e.target.result;
          var objs = JSON.parse(lines);
          
            pasteBlocks(objs);

        }
      }
    

	loadFileFromInput();
	
});

$('.examp').on('click', function(event){
    //console.log($(event.target).data('path'));
    
    $.getJSON($(event.target).data('path'), function(json) {
        pasteBlocks(json);
    });
});

$('body').on('click', '.x', function(e){
	var par = $(e.target).parent('.lay');
	var pref = par.attr('id');
	
	var keys = Object.keys(localStorage);
		for(var key of keys) {
			var keycrop = key.split('_')[0];
			if(keycrop != pref){
			//console.log("Not removed: " + key);
			}else{
				//console.log("Removing: " + key);
				localStorage.removeItem(key);
			}
		}
	$(e.target).parent('.lay').hide();
});

$(window).on('resize', function(){
var con = $('#container');
$('#aside').offset({top: 0, left: con.offset().left + con.width() + 10});
});

$('#aside').pep();