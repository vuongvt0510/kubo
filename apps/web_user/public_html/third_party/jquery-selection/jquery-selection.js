/*
	jQuery - selection - 1.2
	https://github.com/Mr21/jquery-selection
*/

$.plugin_selection = function(parent, options) {
	return new $.plugin_selection.obj(
		parent.jquery
			? parent.eq(0)
			: $(parent),
		options || {}
	);
}

$.plugin_selection.obj = function(jq_parent, options) {
	this.selectableClass = options.selectableClass || 'jqselection-selectable';
	this.selectedClass   = options.selectedClass   || 'jqselection-selected';
	this.numberClass     = options.numberClass     || 'jqselection-number';
	this.app = window;
	this.keyCtrl = false;
	this.keyCtrlLocked = false;
	this.keyShiftEnable = true;
	this.keyShift = false;
	this.elem_mouseLeft = false;
	this.window_mouseLeft = false;
	this.ar_jqSelectable = null;
	this.ar_elSelected = [];
	this.jq_parent = jq_parent;
	this.setWindowEvents();
	this.setParentEvents();
	this.setChildrenEvents(jq_parent.find('.' + this.selectableClass));
};

$.plugin_selection.obj.prototype = {
	// public:
	getArraySelection: function() { return this.ar_elSelected; },
	onElementsAdded:   function(cb) { this.cbAdd = cb; return this; },
	onElementsRemoved: function(cb) { this.cbRem = cb; return this; },
	applyThis: function(app) {
		if (app !== undefined)
			return this.app = app, this;
		return this.app;
	},
	lockCtrlKey: function() {
		this.keyCtrl =
		this.keyCtrlLocked = true;
		return this;
	},
	unlockCtrlKey: function() {
		this.keyCtrl =
		this.keyCtrlLocked = false;
		return this;
	},
	enableShiftKey: function() {
		this.keyShiftEnable = true;
		return this;
	},
	disableShiftKey: function() {
		this.keyShift =
		this.keyShiftEnable = false;
		return this;
	},
	// private:
	select: function(elems) {
		if (elems.length) {
			var	that = this,
				jq_elems = $(elems),
				a = this.ar_elSelected;
			jq_elems
				.addClass(this.selectedClass)
				.append(function() {
					return '<span class="'+ that.numberClass +'">'+ a.push(this) +'</span>';
				});
			if (this.cbAdd)
				this.cbAdd.call(this.app, jq_elems);
		}
	},
	unselect: function(elems) {
		var jq_elems = $(elems);
		jq_elems
			.removeClass(this.selectedClass)
			.children('.' + this.numberClass)
				.remove();
		if (this.cbRem)
			this.cbRem.call(this.app, jq_elems);
	},
	unselectOne: function(elem) {
		var a = this.ar_elSelected;
		a.splice(a.indexOf(elem), 1);
		this.unselect(elem);
		$(a)
			.children('.' + this.numberClass)
			.text(function(i) { return i + 1; });
	},
	unselectAll: function() {
		if (this.ar_elSelected.length) {
			this.unselect(this.ar_elSelected);
			this.ar_elSelected.length = 0;
		}
	},
	click: function(jq_child) {
		var	elems = [],
			el_child = jq_child[0],
			selected = jq_child.hasClass(this.selectedClass);
		if (!selected && !this.keyCtrl) {
			if (this.keyShift && this.ar_elSelected.length)
				elems.push(this.ar_elSelected[this.ar_elSelected.length - 1]);
			this.unselectAll();
		}
		if (!selected || this.keyShift) {
			if (this.keyShift) {
				var elemA = this.ar_elSelected[this.ar_elSelected.length - 1] || elems[0];
				if (elemA !== el_child) {
					var	jq_ar = this.ar_jqSelectable,
						AInd = $.inArray(elemA, jq_ar),
						BInd = $.inArray(el_child, jq_ar),
						incr = AInd < BInd ? 1 : -1,
						i = AInd + incr;
					for (; i !== BInd; i += incr)
						if (!jq_ar.eq(i).hasClass(this.selectedClass))
							elems.push(jq_ar[i]);
				}
			}
			if (!selected)
				elems.push(el_child);
			this.select(elems);
		} else if (selected && this.keyCtrl) {
			this.unselectOne(el_child);
		}
	},
	setChildrenEvents: function(jq_elems) {
		var that = this;
		function up() { that.elem_mouseLeft = false; }
		this.ar_jqSelectable = this.jq_parent.find('.' + this.selectableClass);
		jq_elems
			.each(function() {
				if (!this._jqselection_ready) {
					this._jqselection_ready = true;
					var jq_this = $(this);
					jq_this
						.mouseup(up)
						.mouseleave(up)
						.mousedown(function(e) {
							if (e.button === 0) {
								e.preventDefault();
								that.elem_mouseLeft = true;
								that.click(jq_this);
							}
						});
				}
			});
	},
	setParentEvents: function() {
		var that = this;
		this.jq_parent.on('DOMNodeInserted', function(e) {
			var	jq_elem = $(e.target);
			that.setChildrenEvents(
				jq_elem.hasClass(that.selectableClass)
					? jq_elem
					: jq_elem.find('.' + that.selectableClass)
			);
		});
	},
	setWindowEvents: function() {
		var	that = this,
			docs = [];

		$(window).blur(function() {
			that.keyShift = false;
			if (!that.keyCtrlLocked)
				that.keyCtrl = false;
		});

		function key(k, val) {
			switch (k) {
				case 224: case 91: case 93: case 17:
					if (!that.keyCtrlLocked)
						that.keyCtrl = val;
					break;
				case 16:
					if (that.keyShiftEnable)
						that.keyShift = val;
					break;
			}
		}

		try {
			var win = window;
			do {
				docs.push(win.document);
			} while(win !== win.parent && (win = win.parent));
		} catch(e) {}

		$(docs)
			.keydown(function(e) { key(e.keyCode, true);  })
			.keyup  (function(e) { key(e.keyCode, false); })
			.mousedown(function(e) {
				if (e.button === 0 && !that.elem_mouseLeft && !that.keyCtrl && !that.keyShift)
					that.window_mouseLeft = true;
			})
			.mouseup(function(e) {
				if (e.button === 0 && that.window_mouseLeft) {
					that.window_mouseLeft = false;
					that.unselectAll();
				}
			});
	}
};
