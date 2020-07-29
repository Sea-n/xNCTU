window.Graph = {
    render: function(container, chart) {
        var wrapper = document.createElement('div');
        wrapper.className = 'tchart--wrapper';
        container.appendChild(wrapper);

        return new this.units.TChart({
            container: wrapper,
            data: chart
        });
    },
    units: {
    }
};

(function() {
    var easing = function(st, ed, per, tween) {
        var functions = {
            linear: function(t, b, c, d) {
                return c * t / d + b;
            },

            easeInOutQuad: function(t, b, c, d) {
                t /= d * 0.5;
                if (t < 1) return c * 0.5 * t * t + b;
                t--;
                return -c / 2 * (t * (t - 2) - 1) + b;
            }
        };

        return functions[tween](per, st, ed - st, 1);
    };


    var TAnimator = function(opts) {
        this.composer = opts.composer;
        this.state = opts.state;
        this.queue = {};
        this.queueSize = 0;
        this.step = this.step.bind(this);
    }

    TAnimator.prototype = {
        add: function(params) {
            var i = 0;
//          var j = 0;
            var cur = +new Date();
            var item, param, delta;
            var queue = this.queue;

             while (i < params.length) {
                param = params[i];
                item = queue[param.prop];

                if (!item) {
                    if (param.end == param.state[param.prop]) {
                        param.cbEnd && param.cbEnd(param.state);
                        i++;
                        continue;
                    }

                    item = {
                        lastStart: 1
                    };
                    queue[param.prop] = item;
                    this.queueSize++;
                }

                delta = cur - item.lastStart;

                param.duration *= this.state.speed;
                param.delay *= this.state.speed;

                item.cbEnd = param.cbEnd;
                item.state = param.state;
                item.lastStart = cur;
                item.start = param.state[param.prop];
                item.end = param.end;
                item.startDt = cur + (param.delay || 0);
                item.endDt = item.startDt + (param.duration || 0) - (param.fixed ? 0 : Math.max(param.duration - delta, 0));
                item.tween = param.tween || 'easeInOutQuad';
                item.speed = param.speed;
                item.group = param.group;

                i++;
            }

            if (!this.animFrame) {
                this.animFrame = requestAnimationFrame(this.step);
            }
        },


        get: function(prop) {
            return this.queue[prop];
        },


        step: function() {
            var done = [];
            var cur = +new Date();
            var itemKey, item, time, duration, per, curVal, newVal, j, delayed;
            var group = {top: false, bottom: false};

            for (itemKey in this.queue) {
                item = this.queue[itemKey];
                time = cur;
                duration = item.endDt - item.startDt;
                curVal = item.state[itemKey];
                delayed = time < item.startDt;

                if (time < item.startDt) {
                    time = item.startDt;
                } else if (time > item.endDt) {
                    time = item.endDt;
                }

                per = duration ? (time - item.startDt) / duration : (delayed ? 0 : 1);

                if (per < 1) {
                    if (item.tween == 'exp') {
                        newVal = curVal + (item.end - curVal) * item.speed;
                    } else {
                        newVal = easing(item.start, item.end, per, item.tween);
                    }
                } else {
                    newVal = item.end;
                }

                if (newVal != curVal) {
                    item.state[itemKey] = newVal;
                    group.top = group.top || item.group.top;
                    group.bottom = group.bottom || item.group.bottom;
                } else if (newVal == item.end) {
                    done.push(itemKey);
                }
            }

            //remove animations that is done
            j = 0;
            while (j < done.length) {
                this.queue[done[j]].cbEnd && this.queue[done[j]].cbEnd(this.queue[done[j]].state);
                delete this.queue[done[j]];
                j++;
            }

            this.queueSize -= done.length;

            this.composer.render(group);


            if (!this.queueSize) {
                delete this.animFrame;
            } else {
                this.animFrame = requestAnimationFrame(this.step);
            }
        }
    }

    window.Graph.units.TAnimator = TAnimator;
})();
(function() {
    var units = window.Graph.units;

    var TAreas = function(opts) {
        this.opts = opts;

        if (opts.additional.mini) {
            this.$canvas = document.createElement('canvas');
            this.ctx = this.$canvas.getContext('2d', {alpha: true});
        }
    }

    TAreas.prototype = {
        onResize: function() {
            if (this.opts.additional.mini) {
                var dpi = this.opts.settings.dpi;
                var dims = this.opts.additional.mini ? this.opts.state.dims.mini : this.opts.state.dims.graph;
                this.$canvas.width = dims.w * dpi;
                this.$canvas.height = dims.h * dpi;
                this.cached = '';
            }
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },

        render: function() {
            var i, j, y, o, xScale, yScale, xShift, yShift, hBottom;
            var opts = this.opts;
            var ys = opts.data.ys;
            var state = opts.state;
            var mini = opts.additional.mini;
            var x1 = mini ? state.xg1 : state.x1;
            var x2 = mini ? state.xg2 : state.x2;
            var settings = opts.settings;
            var pTop = settings['PADD' + (mini ? '_MINI_AREA' : '')][0];
            var pRight = settings['PADD' + (mini ? '_MINI_AREA' : '')][1];
            var pBottom = settings['PADD' + (mini ? '_MINI_AREA' : '')][2];
            var pLeft = settings['PADD' + (mini ? '_MINI_AREA' : '')][3];
            var x = opts.data.x;
            var dpi = opts.settings.dpi;
            var xInd1, xInd2;
            var ctx = mini ? this.ctx : this.opts.ctx;
            var prevY = [];
            var totalPerX = [];
//          var totalPerY = [];
            var overlap = mini ? 0 : 0;
            var dims = mini ? state.dims.mini : state.dims.graph;
            var zoomMorph = state.zoomMorph == undefined ? 0 : state.zoomMorph;
            var morph = zoomMorph;
//          var zoom = state.zoomMode;
            var ysLen = ys.length;

            //cache rendered version
            if (mini) {
                var hash = [dims.w, dims.h, state.xg1, state.xg2, this.isDarkMode, state.zoomMode, zoomMorph];
                for (var i = 0; i < ysLen; i++) {
                    hash.push(state['om_' + i]);
                    hash.push(state['f_' + i]);
                }
                hash = hash.join(',');

                if (hash == this.cached) {
                    this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
                    return;
                }

                this.cached = hash;
            }

            xScale = (dims.w - pRight - pLeft) / (x2 - x1);
            xInd1 = Math.floor(units.TUtils.getXIndex(x, x1 - pLeft / xScale));
            xInd2 = Math.ceil(units.TUtils.getXIndex(x, x2 + pRight / xScale));

            xScale *= dpi;
            xShift = (pLeft + (mini ? 0 : dims.l)) * dpi - x1 * xScale;
            hBottom = (dims.h - pBottom + (mini ? 0 : dims.t)) * dpi;

            var visibleCols = [];
            var opacityCols = [];
            var textToCenter = 0; //animation for text moving to center for only 1 selected column
            var fullyVisibleCount = 0;
            var fullyVisibleInd = 0;
            var hasUnfocusedColumns = false;
            for (var i = 0; i < ysLen; i++) {
                o = mini ? state['om_' + i] : state['o_' + i];

                hasUnfocusedColumns = hasUnfocusedColumns || state['f_' + i] < 1;

                if (o < 1 && o > 0) {
                    textToCenter = o;
                }

                if (o > 0) {
                    visibleCols.push(i);
                    opacityCols.push(o);

                    if (o == 1 && state['e_' + i]) {
                        fullyVisibleCount++;
                        fullyVisibleInd = visibleCols.length - 1;
                    }
                }
            }

            var colsLen = visibleCols.length;
            textToCenter = fullyVisibleCount == 1 ? textToCenter : 1;

//          y1 = mini ? state['y1m'] : state['y1'];
//          y2 = mini ? state['y2m'] : state['y2'];
            var optData = units.TUtils.simplifyData('line', x, ys, xScale, xShift, visibleCols, xInd1, xInd2, dims.w - pRight - pLeft);

            xInd1 = optData.xInd1;
            xInd2 = optData.xInd2;
            var optX = optData.x;
            var optYs = optData.ys;
            var hasGapsInData = false;

            for (var j = xInd1; j <= xInd2; j++) {
                prevY[j] = 0;
                totalPerX[j] = 0;
                for (var i = 0; i < colsLen; i++) {
                    totalPerX[j] += (optYs[visibleCols[i]].y[j] || 0) * opacityCols[i];
                }
                if (totalPerX[j] == 0) {
                    hasGapsInData = true;
                }
            }

            if (hasGapsInData || hasUnfocusedColumns) {
                ctx.fillStyle = this.isDarkMode ? '#242f3e' : '#fff';
                ctx.fillRect(0, 0, dims.w * dpi, dims.h * dpi);
            }

            //calc totals for fractional period so all animations will be transformed for pie representation
            if (zoomMorph > 0 && !mini) {
                if (morph == 1) {
                    this.savedX1 = x1;
                    this.savedX2 = x2;
                }

                if (morph < 1) {
                    var x1AnimItem = this.opts.animator.get('x1');
                    var x2AnimItem = this.opts.animator.get('x2');
                    var x1End = x1AnimItem ? x1AnimItem.end : this.opts.state['x1'];
                    var x2End = x2AnimItem ? x2AnimItem.end : this.opts.state['x2'];
                    var xInd1Real = units.TUtils.getXIndex(x, this.opts.state.zoomDir == -1 ? this.savedX1 : x1End, true);
                    var xInd2Real = units.TUtils.getXIndex(x, this.opts.state.zoomDir == -1 ? this.savedX2 : x2End, true);
                } else {
                    var xInd1Real = units.TUtils.getXIndex(x, x1, true);
                    var xInd2Real = units.TUtils.getXIndex(x, x2, true);
                }
                xInd2Real--;

                var xInd1RealFloor = Math.floor(xInd1Real);
                var xInd1RealCeil = Math.ceil(xInd1Real);
                var xInd2RealFloor = Math.floor(xInd2Real);
                var xInd2RealCeil = Math.ceil(xInd2Real);
                var totalForAll = 0;
                var totalPerItem = [];

                for (var i = 0; i < colsLen; i++) {
                    totalPerItem[i] = 0;
                    var tmpY = ys[visibleCols[i]].y;

                    for (var j = xInd1RealCeil; j <= xInd2RealFloor; j++) {
                        var tmp = (tmpY[j] || 0) * opacityCols[i];
                        totalPerItem[i] += tmp;
                        totalForAll += tmp;
                    }

                    //partly visible data from left side
                    var tmp = ((xInd1RealCeil - xInd1Real) * (tmpY[xInd1RealFloor] || 0)) * opacityCols[i];
                    totalPerItem[i] += tmp;
                    totalForAll += tmp;

                    //partly visible data from right side
                    var tmp = ((xInd2Real - xInd2RealFloor) * (tmpY[xInd2RealCeil] || 0)) * opacityCols[i];
                    totalPerItem[i] += tmp;
                    totalForAll += tmp;
                }

                //calc angles for pie representation
                var elastic =  this.opts.state.zoomDir == 1 ? Math.pow(Math.min(Math.max(morph < 0.85 ? (morph-0.65)/0.2 : 1 - (morph-0.9)/0.15, 0), 1), 0.8) : this.prevElastic;
                var prevAngle = 2 * Math.PI - Math.PI / (7 - morph) - Math.PI / 8 * elastic;
                var initAngle = prevAngle;


                if (this.opts.state.zoomDir == 1) {
                    morph = Math.min(Math.max((morph - 0.25) / 0.4, 0), 1);
                    this.prevElastic = elastic;
                } else {
                    morph = Math.min(Math.max((morph * 2.4) - 1.4, 0), 1);
                }


                var angles = [];
                var radius = settings.PIE_RADIUS * (zoomMorph < 1 ? 2.31 : 1) * dpi; //during zoom animation use clipping, then use plain geometry to create sectors
                var cx = dpi * (dims.w / 2 + dims.l);
                var cy = dpi * (dims.h / 2 + dims.t + 2);
                var rLen =  2 * Math.PI * radius / dpi;
                var pointsPerArcLen = 1 / 13; //1 point per each 10 pixels of arc
                for (var i = 0; i < colsLen; i++) {
                    var percentage = totalPerItem[i] / totalForAll;
                    percentage = percentage || 0; //absent data
                    var len = 2 * Math.PI * percentage;
                    var newAngle = prevAngle - len;
                    var additionalPoints = Math.round(percentage * rLen * pointsPerArcLen);
                    if (i == colsLen - 1) newAngle = initAngle - 2 * Math.PI;
                    var overlapAng = Math.PI * 2 * 0.1 / (rLen);
                    var yItem = ys[visibleCols[i]];
                    angles.push({
                        st: prevAngle + overlapAng,
                        ed: newAngle - overlapAng,
                        mid: prevAngle - len / 2 - overlapAng / 2,
                        additionalPoints: Math.max(additionalPoints, 4),
                        percentage: percentage == 0 ? 0 : Math.max(Math.round(percentage * 100), 1),
                        percentageText: percentage == 0 ? '' : (percentage < 0.01 ? '<1%' : Math.round(percentage * 100) + '%'),
                        ind: visibleCols[i],
                        value: totalPerItem[i],
                        label: yItem.label,
                        color: this.isDarkMode ? yItem.colors_n[2] : yItem.colors_d[2]
                    });


                    prevAngle = newAngle;
                }

                state.pieAngles = angles;
            }

            yScale = dpi * (dims.h - pTop - pBottom + (mini ? 0 : -4));
            yShift = (dims.h - pBottom + (mini ? 0 : dims.t)) * dpi;

            var colInd = 0;

            for (var i = 0; i < ysLen; i++) {
                o = mini ? state['om_' + i] : state['o_' + i];

                if (o > 0) {
                    y = optYs[i].y;

                    var k = o * yScale;

                    ctx.fillStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];
                    ctx.globalAlpha = state['f_' + i] * 0.9 + 0.1;
                    ctx.beginPath();

                    if (zoomMorph == 0 || !mini) {
                        //use regular version to skip complex math evaluations
                        //despite of fact that they produce same result for morph == 0
                        if (zoomMorph == 0) {

                            if (i > 0) {
                                ctx.moveTo(optX[xInd2] * xScale + xShift << 0, hBottom - prevY[xInd2] + overlap << 0);
                                for (var j = xInd2 - 1; j >= xInd1; j--) {
                                    ctx.lineTo(optX[j] * xScale + xShift << 0, hBottom - prevY[j] + overlap << 0);
                                }
                            } else {
                                ctx.moveTo(optX[xInd2] * xScale + xShift << 0, hBottom << 0);
                                ctx.lineTo(optX[xInd1] * xScale + xShift << 0, hBottom << 0);
                            }

                            if (colInd < colsLen - 1 || hasGapsInData) {
                                for (var j = xInd1; j <= xInd2; j++) {
                                    var curY = (yShift - ((y[j] * k / totalPerX[j]) || 0));
                                    var curH = hBottom - curY;
                                    var sy = prevY[j] + curH;
                                    if (sy > yScale) sy = yScale;
                                    ctx.lineTo(optX[j] * xScale + xShift << 0, hBottom - sy << 0);
                                    prevY[j] += curH;
                                }
                            } else {
                                ctx.lineTo(optX[xInd1] * xScale + xShift << 0, hBottom - yScale << 0);
                                ctx.lineTo(optX[xInd2] * xScale + xShift << 0, hBottom - yScale << 0);
                            }
                        } else {
                            //magic starts here
                            var calcTrans = function(fromX, fromY, toAngle, toR) {

                                var sx = 0;
                                var sy = 0;
                                if (selectionOffset && fullyVisibleCount > 1) {
                                    sx = Math.cos(angles[colInd].mid) * selectionOffset * 8 * dpi;
                                    sy = -Math.sin(angles[colInd].mid) * selectionOffset * 8 * dpi;
                                }

                                if (toR > radius) toR = radius;
                                var fromAngle = Math.atan2(cy - fromY, fromX - cx);
                                fromAngle = fromAngle < 0 ? Math.PI * 2 + fromAngle : fromAngle;
                                var fromR = Math.pow((cy - fromY) * (cy - fromY) + (fromX - cx) * (fromX - cx), 0.5);

                                if (Math.abs(toAngle - fromAngle) > Math.PI * (colsLen == 1 ? 1.5 : 1)) {
                                    toAngle -= Math.PI * 2;
                                }
                                if (toAngle < -Math.PI * 2) {
                                    toAngle -= -Math.PI * 2;
                                }

                                var ang = fromAngle + morph * (toAngle - fromAngle);
                                var r = fromR + morph * (toR - fromR);
                                var res = [
                                    cx + Math.cos(ang) * r + sx,
                                    cy - Math.sin(ang) * r + sy
                                ];

                                return res;
                            };

                            var additionalSteps = (zoomMorph < 1 ? 4 : angles[colInd].additionalPoints);
                            var res;
                            var dist;
                            var cBot = false, cTop = false, xj;
                            var selectionOffset = state['pieInd_' + visibleCols[colInd]] || 0;

                            if (angles[colInd].percentage == 0) {
                                ctx.globalAlpha = 0;
                            }

                            res = calcTrans(optX[xInd2] * xScale + xShift, hBottom - prevY[xInd2], angles[0].st, radius);
                            ctx.moveTo(res[0], res[1]);

                            if (colInd > 0) {
                                for (var j = xInd2 - 1; j >= xInd1; j--) {
                                    xj = optX[j] * xScale + xShift;
                                    if (xj == cx) cBot = true;
                                    if (xj >= cx) {
                                        dist = (xj - cx) / (dims.w * dpi / 2);
                                        if (morph == 0) dist = 0;
                                        res = calcTrans(xj, hBottom - prevY[j] + overlap, angles[0].st, radius * dist);
                                    } else {
                                        if (!cBot) {
                                            cBot = true;
                                            var sc = (cx - xj) / (optX[j + 1] * xScale + xShift - xj);
                                            var sy1 = hBottom - prevY[j] + overlap;
                                            var sy2 = hBottom - prevY[j + 1] + overlap;
                                            res = calcTrans(cx, sy1 + sc * (sy2 - sy1), angles[colInd].st, 0);
                                            ctx.lineTo(res[0], res[1]);
                                        }
                                        dist = (cx - xj) / (dims.w * dpi / 2);
                                        res = calcTrans(xj, hBottom - prevY[j] + overlap, angles[colInd].st, radius * dist);
                                    }
                                    ctx.lineTo(res[0], res[1]);
                                }
                            } else {
                                res = calcTrans(
                                    optX[xInd1] * xScale + xShift,
                                    hBottom,
                                    angles[0].st,
                                    radius
                                );
                                ctx.lineTo(res[0], res[1]);
                            }

                            if (colInd < colsLen - 1) {

                                for (var j = 0; j <= additionalSteps; j++) {
                                    var curY = (yShift - ((y[xInd1] * k / totalPerX[xInd1]) || 0));
                                    var curH = hBottom - curY;
                                    var sy1 = hBottom - prevY[xInd1] + overlap;
                                    var sy2 = hBottom - prevY[xInd1] - curH;

                                    res = calcTrans(
                                        optX[xInd1] * xScale + xShift,
                                        sy1 + (j / additionalSteps) * (sy2 - sy1),
                                        angles[colInd].st + (j / additionalSteps) * (angles[colInd].ed - angles[colInd].st),
                                        radius
                                    );

                                    ctx.lineTo(res[0], res[1]);
                                }

                                for (var j = xInd1; j <= xInd2; j++) {

                                    var curY = (yShift - ((y[j] * k / totalPerX[j]) || 0));
                                    var curH = hBottom - curY;
                                    xj = optX[j] * xScale + xShift;

                                    if (xj == cx) cTop = true;
                                    if (xj <= cx) {
                                        dist = (cx - xj) / (dims.w * dpi / 2);
                                        var xjprev = xj;
                                        var syprev = hBottom - prevY[j] - curH;
                                        res = calcTrans(xj, syprev, angles[colInd].ed, radius * dist);
                                    } else {
                                        if (!cTop) {
                                            cTop = true;
                                            var sc = (cx - xjprev) / (xj - xjprev);
                                            var sy1 = syprev;
                                            var sy2 = hBottom - prevY[j] - curH;
                                            res = calcTrans(cx, sy1 + sc * (sy2 - sy1), angles[colInd].ed, 0);
                                            ctx.lineTo(res[0], res[1]);
                                        }
                                        dist = (xj - cx) / (dims.w * dpi / 2);
                                        if (morph == 0) dist = 0;
                                        res = calcTrans(xj, hBottom - prevY[j] - curH, angles[0].st, radius * dist);
                                    }

                                    ctx.lineTo(res[0], res[1]);
                                    prevY[j] += curH;
                                }

                                if (xj < cx) { //last day, haack
                                    if (!cTop) {
                                        res = calcTrans(cx, sy1, angles[colInd].ed, 0);
                                        ctx.lineTo(res[0], res[1]);
                                    }
                                }
                            } else {
                                for (var j = 0; j <= additionalSteps; j++) {
                                    res = calcTrans(
                                        (optX[xInd1] + (j / additionalSteps) * (optX[xInd2] - optX[xInd1])) * xScale + xShift,
                                        0,
                                        angles[colInd].st + (j / additionalSteps) * (angles[0].st - 2 * Math.PI  - angles[colInd].st),
                                        radius
                                    );

                                    ctx.lineTo(res[0], res[1]);
                                }
                            }
                        }

                    } else {
                        var xw = this.opts.data.mainPeriodLen * xScale;

                        ctx.moveTo(optX[xInd2] * xScale + xw + xShift, hBottom - prevY[xInd2]);

                        if (i > 0) {
                            ctx.lineTo(optX[xInd2] * xScale + xShift, hBottom - prevY[xInd2]);

                            for (var j = xInd2; j >= xInd1 + 1; j--) {
                                ctx.lineTo(optX[j] * xScale + xShift, hBottom - (prevY[j] + morph * (prevY[j - 1] - prevY[j])) + overlap);
                                ctx.lineTo(optX[j - 1] * xScale + xShift, hBottom - prevY[j - 1] + overlap);
                            }
                        } else {
                            ctx.lineTo(optX[xInd1] * xScale + xShift, hBottom);
                        }

                        if (colInd < colsLen - 1) {
                            for (var j = xInd1; j <= xInd2 - 1; j++) {
                                var curY = (yShift - ((y[j] * k / totalPerX[j]) || 0));
                                var curH = hBottom - curY;

                                if (j == xInd1) {
                                    ctx.lineTo(optX[xInd1] * xScale + xShift, hBottom - prevY[xInd1] - curH);
                                }

                                var curY = (yShift - ((y[j + 1] * k / totalPerX[j + 1]) || 0));
                                var curHNext = hBottom - curY;

                                var yTo = prevY[j] + curH;
                                var yFrom = prevY[j + 1] + curHNext;

                                ctx.lineTo(optX[j+1] * xScale + xShift, hBottom - (yFrom + morph * (yTo - yFrom)));
                                ctx.lineTo(optX[j+1] * xScale + xShift, hBottom - yFrom);

                                if (j == xInd2 - 1) {
                                    ctx.lineTo(optX[xInd2] * xScale + xw + xShift, hBottom - prevY[xInd2] - curHNext);
                                }

                                prevY[j] += curH;
                            }
                        } else {
                            ctx.lineTo(optX[xInd1] * xScale + xShift << 0, hBottom - yScale << 0);
                            ctx.lineTo(optX[xInd2] * xScale + xShift << 0, hBottom - yScale << 0);
                        }

                        prevY[xInd2] += curHNext;
                    }

                    ctx.closePath();
                    ctx.fill();

                    //texts
                    if (!mini && zoomMorph > 0 && angles[colInd].percentageText) {
                        var opacity = Math.pow(morph, this.opts.state.zoomDir == 1 ? 4 : 20) * o * (state['f_' + i] * 0.9 + 0.1);
                        var fontSize = Math.max(Math.min(angles[colInd].percentage * 2, 26), 10);
                        var rad = settings.PIE_RADIUS;
                        var offset = rad * 2 / 3;
                        var cosVal = Math.cos(angles[colInd].mid);
                        var sinVal = Math.sin(angles[colInd].mid);
                        var isOutboard = angles[colInd].percentage < opts.data.pieLabelsPercentages.outboard;

                        var sx = 0;
                        var sy = 0;
                        if (selectionOffset && fullyVisibleCount > 1) {
                            sx = cosVal * selectionOffset * 8 * dpi;
                            sy = -sinVal * selectionOffset * 8 * dpi;
                        }

                        ctx.fillStyle = 'white';
                        ctx.textAlign = 'center';
                        ctx.globalAlpha = opacity;

                        if (angles[colInd].percentage < opts.data.pieLabelsPercentages.hoverOnly) {
                            ctx.globalAlpha = selectionOffset * opacity;
                        }

                        if (isOutboard) {
                            var fontSize = Math.max(fontSize, 14);
                            offset = rad + fontSize / 3 + 13;
                            ctx.fillStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];
                            ctx.lineWidth = 1;
                            ctx.strokeStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];

                            var lx1 = cx + sx + (cosVal * (rad - 1)) * dpi;
                            var ly1 = cy + sy - (sinVal * (rad - 1)) * dpi;
                            var lx2 = cx + sx + (cosVal * (rad + 6 * (1 - selectionOffset) - 1)) * dpi;
                            var ly2 = cy + sy - (sinVal * (rad + 6 * (1 - selectionOffset) - 1)) * dpi;

                            ctx.beginPath();
                            ctx.moveTo(lx1, ly1);
                            ctx.lineTo(lx2, ly2);
                            ctx.stroke();
                        }

                        var dx = (cosVal * offset) * (fullyVisibleInd == colInd ? textToCenter : 1);
                        var tx = cx + sx + dx * dpi + (isOutboard ? (fontSize / 4 * angles[colInd].percentageText.length * dx / offset) * dpi : 0);
                        var ty = cy + sy - (sinVal * offset) * (fullyVisibleInd == colInd ? textToCenter : 1) * dpi;

                        ctx.font = 'bold ' + fontSize * dpi + 'px ' + opts.settings.FONTS;
                        ctx.fillText(angles[colInd].percentageText, tx, ty + fontSize * dpi / 2.9);//fontSize * dpi / 2.9 cause text render point is baseline

                        ctx.globalAlpha = 1;
                    }

                    colInd++;
                }
            }

            ctx.globalAlpha = 1;

            mini && this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
        }
    }

    units.TAreas = TAreas;
})();
(function() {
    var units = window.Graph.units;

    var TAxisX = function(opts) {
        this.opts = opts;
        this.ctx = opts.ctx;
        this.items = {};

        this.setAnimation(false);

        this.deleteItem = this.deleteItem.bind(this);
    }

    TAxisX.prototype = {
        onResize: function() {
            this.setAnimation(false);
        },


        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },


        setAnimation: function(enabled) {
            this.noAnimation = !enabled;
        },


        hideItem: function(ind, k) {
            this.items[ind].tp = 2;

            this.opts.animator.add([{
                prop: 'ox_' + ind,
                state: this.items[ind].state,
                end: 0,
                duration: this.noAnimation ? 0 : 200 * k,
                tween: 'linear',
                group: {top: true},
                cbEnd: this.deleteItem
            }]);
        },


        deleteItem: function(state) {
            delete this.items[state.ind];
        },


        render: function(opacity) {

            var opts = this.opts;
            var dpi = opts.settings.dpi;
            var x = opts.data.x;
            var state = opts.state;
            var pRight = opts.settings.PADD[1];
            var pLeft = opts.settings.PADD[3];
            var animator = opts.animator;
//          var xLen = x.length;
            var dims = this.opts.state.dims.axisX;
            var dimsDates = this.opts.state.dims.dates;
            var zoomMode = state.zoomMode;
            var zoomMorph = state.zoomMorph == undefined ? 0 : state.zoomMorph;

            var x1 = Math.floor(units.TUtils.getXIndex(x, state.x1));
            var x2 = Math.ceil(units.TUtils.getXIndex(x, state.x2));
            var x1End = x1;
            var x2End = x2;

            var isPointHasWidth = opts.graphStyle == 'bar' || opts.graphStyle == 'step';

            //fast calculation of average space occupied by label
            var space = (state.zoomMode && opts.data.details ? opts.data.details.maxXTickLength : opts.data.maxXTickLength) * 9;

            var offsetForBarGraphMain = isPointHasWidth ? this.opts.data.mainPeriodLen : 0;
            var offsetForBarGraphDetail = isPointHasWidth ? this.opts.data.detailPeriodLen : 0;
            var offsetForBarGraph = offsetForBarGraphMain + (offsetForBarGraphDetail - offsetForBarGraphMain) * zoomMorph;
            var offsetForBarGraphScale = offsetForBarGraphMain * (1 - zoomMorph);

            var xStepMain = (dims.w - pRight - pLeft) / Math.round((state.x2 - state.x1 + offsetForBarGraphMain) / this.opts.data.mainPeriodLen);
            var xStepDetail = (dims.w - pRight - pLeft) / Math.round((state.x2 - state.x1 + offsetForBarGraphDetail) / this.opts.data.detailPeriodLen);
            var xStep = xStepMain + (xStepDetail - xStepMain) * zoomMorph;

            var skipEachMain = Math.pow(2, Math.ceil(Math.log2(space / xStepMain)));
            var skipEachDetail = Math.pow(2, Math.ceil(Math.log2(space / xStepDetail)));
            var lxScale = (dims.w - pLeft - pRight) / (state.x2 - state.x1 + offsetForBarGraphScale);

            if (skipEachMain < 1) {
                skipEachMain = 1;
            }
            if (skipEachDetail < 1) {
                skipEachDetail = 1;
            }

            this.ctx.font = 11 * dpi + 'px ' + opts.settings.FONTS;
            this.ctx.textAlign = 'center';
            this.ctx.fillStyle = this.isDarkMode ? this.opts.data.axis_n.x : this.opts.data.axis_d.x;

            //for fast handle move reduce animation duration
            var changeSpeed = this.prevXStep ? (this.prevXStep > xStepMain ? this.prevXStep / xStepMain  : xStepMain / this.prevXStep) : 1;
            var k = 1 / Math.pow(changeSpeed, 5);

            if (zoomMode && zoomMorph == 1) {
                k /= 2;
            }

            this.prevXStep = xStepMain;
            var x1 = Math.max(x1 - Math.ceil((pLeft + space * 0.5) / xStep), 0);
            var x2 = Math.min(x2 + Math.ceil((pRight + space * 0.5) / xStep), x.length - 1);

            if (zoomMode) {
                var x1AnimItem = this.opts.animator.get('x1');
                var x2AnimItem = this.opts.animator.get('x2');
                x1End = x1AnimItem ? x1AnimItem.end : this.opts.state['x1'];
                x2End = x2AnimItem ? x2AnimItem.end : this.opts.state['x2'];
                x1End = Math.floor(units.TUtils.getXIndex(x, x1End));
                x2End = Math.floor(units.TUtils.getXIndex(x, x2End));
            }

            if (zoomMode) {
                var tmp1 = Math.max(x[this.opts.state.detailInd1], this.opts.state.xMainMin);
                var tmp2 = Math.min(x[this.opts.state.detailInd2], this.opts.state.xMainMax);
                var dtOffset = Math.round((tmp2 - tmp1) / this.opts.data.mainPeriodLen) + (isPointHasWidth ? 0 : 1);
            }


            for (var i = x1; i <= x2; i++) {
                var shown = (i % skipEachMain) == 0;
                var prefix = 'm';

                if (zoomMode) {
                    if (i < this.opts.state.detailInd1) {
                        shown = (i % skipEachMain) == 0 && zoomMorph < 1;
                    } else if (i <= this.opts.state.detailInd2) {
                        shown = (Math.max(i - this.opts.state.detailInd1, 0) % skipEachDetail) == 0;
                        prefix = 'd';
                    } else {
                        shown = (Math.max(i - (this.opts.state.detailInd2 - this.opts.state.detailInd1 + 1 - dtOffset), 0) % skipEachMain) == 0 && zoomMorph < 1;
                    }
                }

                var id = x[i] + prefix;
                var item = this.items[id];

                if (shown) {
                    if (!item) { //not exist or removed
                        item = {
                            tp: 1,
                            xi: x[i],
                            i:i,
                            state: {
                                ind: id
                            }
                        };
                        item.state['ox_' + id] = 0;
                        this.items[id] = item;

                        animator.add([{
                            prop: 'ox_' + id,
                            state: item.state,
                            end: 1,
                            duration: this.noAnimation ? 0 : 200 * k,
                            tween: 'linear',
                            group: {top: true}
                        }]);
                    } else if (item.tp == 2) { //is hiding
                        item.tp = 1;

                        animator.add([{
                            prop: 'ox_' + id,
                            state: item.state,
                            end: 1,
                            duration: this.noAnimation ? 0 : 200 * k,
                            tween: 'linear',
                            group: {top: true}
                        }]);
                    }
                } else {
                    if (item && item.tp == 1) { //is showing or shown
                        this.hideItem(id, k);
                    }
                }

                if (item && item.state['ox_' + id] > 0) {
                    var xc = (item.xi - state.x1 + offsetForBarGraph / 2 ) * lxScale + pLeft;

                    this.ctx.globalAlpha = item.state['ox_' + id] * opacity;

                    if (xc + space / 2 >= dims.l && xc - space / 2 <= dims.l + dims.w) {
                        //first and last labels manual align
                        var xAligned = (xc + dims.l) * dpi;

                        this.ctx.fillText(opts.data.datesShort[i], xAligned, (dims.t + 9) * dpi);
                    }
                }
            }


            //remove the old ones, which is outside the current range
            for (var i in this.items) {
                var item = this.items[i];
                if (item.tp == 1 && ((item.xi < state.x1 - pLeft / lxScale) || (item.xi > state.x2 + pRight / lxScale))) {
                    this.hideItem(i, k);
                }
            }

            this.ctx.globalAlpha = 1;

            var datesStr;
            if (zoomMode && zoomMorph == 1) {
                x2End--;
            }

            if (x2End < x1End) x2End = x1End;

            if (opts.data.datesRange[x1End] == opts.data.datesRange[x2End]) {
                datesStr = opts.data.datesRange[x1End];
            } else {
                datesStr = opts.data.datesRange[x1End] + ' – ' + opts.data.datesRange[x2End];
            }

            var fontSize = 13;
            if (dims.w < 375) {
                fontSize = 11;
            }

            this.ctx.font = 'bold ' + fontSize * dpi + 'px ' + opts.settings.FONTS;
            this.ctx.textAlign = 'right';
            this.ctx.fillStyle = this.isDarkMode ? '#fff' : '#000';
            this.ctx.fillText(datesStr, (dimsDates.l + dimsDates.w) * dpi, (dimsDates.t + dimsDates.h - 7) * dpi);
        }
    }

    units.TAxisX = TAxisX;
})();
(function() {
    var units = window.Graph.units;

    var TAxisY = function(opts) {
        this.opts = opts;
        this.ctx = opts.ctx;
        this.uuid = 1;
        this.items = {};

        this.setAnimation(false);
        this.setForceUpdate(false);

        this.deleteItem = this.deleteItem.bind(this);
    }

    TAxisY.prototype = {

        onResize: function() {
            this.setAnimation(false);
            this.setForceUpdate(false);
        },


        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },


        setAnimation: function(enabled) {
            this.noAnimation = !enabled;
        },


        setForceUpdate: function(enabled) {
            this.forceUpdate = enabled;
        },


        deleteItem: function(state) {
            delete this.items[state.id];
        },


        render: function(opacity) {
            var calcDataLeft;
            var calcDataRight;

            if (this.opts.pairY) {
                calcDataLeft = this.calcAxisData('y1_0', 'y2_0');
                calcDataRight = this.calcAxisData('y1_1', 'y2_1');

                //left axis is main, if both need animation priority goes to left
                if ((calcDataRight.needAnimation && !calcDataLeft.needAnimation) || this.opts.state['o_0'] < 1) {
                    this.updateAxisState('y1_1', 'y2_1', 'numRight', calcDataRight, calcDataLeft, calcDataRight);
                } else {
                    this.updateAxisState('y1_0', 'y2_0', 'numLeft', calcDataLeft, calcDataLeft, calcDataRight);
                }
            } else {
                calcDataLeft = this.calcAxisData('y1', 'y2');
                this.updateAxisState('y1', 'y2', 'numLeft', calcDataLeft, calcDataLeft, calcDataLeft);
            }

            this.renderState(opacity);
        },


        calcAxisData: function(y1Name, y2Name) {
            var state = this.opts.state;
            var pTop = this.opts.settings.PADD[0];
            var pBottom = this.opts.settings.PADD[2];
//          var linesCount = Math.floor(this.opts.settings.Y_AXIS_RANGE);
            var withAnimation = false;

            var y1AnimItem = this.opts.animator.get(y1Name);
            var y2AnimItem = this.opts.animator.get(y2Name);
            var y1 = y1AnimItem ? y1AnimItem.end : state[y1Name];
            var y2 = y2AnimItem ? y2AnimItem.end : state[y2Name];

            var yRealStep = Math.round((y2 - y1) / this.opts.settings.Y_AXIS_RANGE);
            var yRealStart = y1;

            var yCurRange = state[y2Name] - state[y1Name];
            var changeSpeedFirst = state[y1Name] > y1 ? state[y1Name] / y1 : y1 / state[y1Name];
            var changeSpeedLast = state[y2Name] > y2 ? state[y2Name] / y2 : y2 / state[y2Name];

            var yEndRange = y2 - y1;
            var yScaleCur = (this.opts.state.dims.axisYLines.h - pTop - pBottom) / yCurRange;
            var yScaleEnd = (this.opts.state.dims.axisYLines.h - pTop - pBottom) / yEndRange;

            if (changeSpeedFirst > 1.05 || changeSpeedLast > 1.05 || this.forceUpdate) {
                withAnimation = true;
            }

            //this.items[0] check that items have been created
            withAnimation = this.items[0] && withAnimation && !this.noAnimation && !this.animationInProgress;

            return {
                needAnimation: withAnimation,
                y1: y1,
                y2: y2,
                yRealStep: yRealStep,
                yRealStart: yRealStart,
                yScaleCur: yScaleCur,
                yScaleEnd: yScaleEnd
            };
        },


        updateAxisState: function(y1Name, y2Name, numName, baseData, leftData, rightData) {
            var opts = this.opts;
//          var settings = opts.settings;
//          var dpi = opts.settings.dpi;
            var state = opts.state;
 //         var pTop = opts.settings.PADD[0];
            var pBottom = opts.settings.PADD[2];
 //         var pLeft = opts.settings.PADD[3];
 //         var pRight = opts.settings.PADD[1];
            var animator = opts.animator;
            var item;
            var linesCount = Math.floor(opts.settings.Y_AXIS_RANGE);
            var startedAtLeastOne = false;
            var dims = this.opts.state.dims.axisYLines;

            if (baseData.needAnimation) {
                this.animationInProgress = true;
            }

            for (var i = 0; i <= linesCount; i++) {
                var numReal = baseData.yRealStart + Math.round(baseData.yRealStep * i);
                var numRealLeft = leftData.yRealStart + Math.round(leftData.yRealStep * i);
                var numRealRight = rightData.yRealStart + Math.round(rightData.yRealStep * i);
                var formatter = units.TUtils.getFormatter('yTickFormatter', opts.data, state.zoomMorph);

                var numDisplayLeftStr = formatter(numRealLeft, leftData.yRealStep);

                //then first graph is hidden we will show rounded range and numbers for second one
                //else we will fit second graphic exactly to same bound as first one,
                //thus loosing the ability to use rounded numvers and range for second graph (but showing it exactly as needed)
                //upd disabled let it always be fractional even then first graph is not seen
                //to avoid small 2nd graph scale on first graph is off (cause no fit to first graph is needed)
                var numDisplayRightStr = formatter(Math.max(numRealRight, 0), leftData.yRealStep, true/*, this.opts.state['e_0']*/);

                if (baseData.needAnimation) {
                    var oldFrom = dims.t + dims.h - pBottom - (numReal - baseData.y1) * baseData.yScaleEnd;
                    var oldTo = dims.t + dims.h - pBottom - (this.items[i][numName] - baseData.y1) * baseData.yScaleEnd;
                    var newFrom = dims.t + dims.h - pBottom - (numReal - state[y1Name]) * baseData.yScaleCur;
                    var newTo = dims.t + dims.h - pBottom - (numReal - baseData.y1) * baseData.yScaleEnd;

                    //if stays on the same pos - no animation
                    if (Math.abs(oldTo - newTo) < 1) {
                        this.items[i] = {
                            numLeft: numRealLeft,
                            strLeft: numDisplayLeftStr,
                            numRight: numRealRight,
                            strRight: numDisplayRightStr,
                            y: newTo
                        };
                    } else {
                        startedAtLeastOne = true;

                        //hide previous one static
                        this.uuid++;
                        item = {
                            animated: true,
                            strLeft: this.items[i].strLeft,
                            strRight: this.items[i].strRight,
                            oProp: 'oyt_' + this.uuid,
                            yProp: 'yyt_' + this.uuid,
                            state: {
                                id: 't_' + this.uuid
                            }
                        };
                        item.state[item.oProp] = 1;
                        item.state[item.yProp] = oldFrom;
                        this.items[item.state.id] = item;

                        animator.add([{
                            prop: item.oProp,
                            state: item.state,
                            end: 0,
                            duration: this.noAnimation ? 0 : 200,
                            tween: 'linear',
                            group: {top: true}
                        }, {
                            prop: item.yProp,
                            state: item.state,
                            end: oldTo,
                            duration: this.noAnimation ? 0 : (!this.forceUpdate ? 500 : 333),
                            fixed: !this.forceUpdate,
                            tween: !this.forceUpdate ? 'exp' : null,
                            speed: 0.18,
                            group: {top: true},
                            cbEnd: this.deleteItem
                        }]);

                        delete this.items[i];

                        //show new which one will became static after animation end
                        this.uuid++;
                        item = {
                            animated: true,
                            strLeft: numDisplayLeftStr,
                            strRight: numDisplayRightStr,
                            oProp: 'oy_' + i,
                            yProp: 'yy_' + i,
                            state: {
                                id: i,
                                numLeft: numRealLeft,
                                strLeft: numDisplayLeftStr,
                                numRight: numRealRight,
                                strRight: numDisplayRightStr,
                            }
                        };
                        item.state[item.oProp] = 0;
                        item.state[item.yProp] = newFrom;
                        this.items[item.state.id] = item;

                        animator.add([{
                            prop: item.oProp,
                            state: item.state,
                            end: 1,
                            duration: this.noAnimation ? 0 : 200,
                            tween: 'linear',
                            group: {top: true}
                        }, {
                            prop: item.yProp,
                            state: item.state,
                            end: newTo,
                            duration: this.noAnimation ? 0 : (!this.forceUpdate ? 500 : 333),
                            fixed: !this.forceUpdate,
                            tween: !this.forceUpdate ? 'exp' : null,
                            speed: 0.18,
                            group: {top: true},
                            cbEnd: function(state) {
                                this.items[state.id] = {
                                    numLeft: state.numLeft,
                                    strLeft: state.strLeft,
                                    numRight: state.numRight,
                                    strRight: state.strRight,
                                    y: state['yy_' + state.id]
                                };

                                clearTimeout(this.animationEndTimeout);
                                this.animationEndTimeout = setTimeout(function() {
                                    this.animationInProgress = false;
                                }.bind(this), 30);
                            }.bind(this)
                        }]);
                    }
                } else {
                    if (this.items[i] && this.items[i].animated) {
                        this.items[i].numLeft = numRealLeft;
                        this.items[i].strLeft = numDisplayLeftStr;
                        this.items[i].numRight = numRealRight;
                        this.items[i].strRight = numDisplayRightStr;
                        this.items[i].state.numLeft = numRealLeft;
                        this.items[i].state.strLeft = numDisplayLeftStr;
                        this.items[i].state.numRight = numRealRight;
                        this.items[i].state.strRight = numDisplayRightStr;
                    } else {
                        this.items[i] = {
                            numLeft: numRealLeft,
                            strLeft: numDisplayLeftStr,
                            numRight: numRealRight,
                            strRight: numDisplayRightStr,
                            y: dims.t + dims.h - pBottom - (numReal - baseData.y1) * baseData.yScaleEnd
                        };
                    }
                }
            }

            if (baseData.needAnimation && !startedAtLeastOne) {
                this.animationInProgress = false;
            }

            this.forceUpdate = false;
        },


        renderState: function (opacity) {
            var dpi = this.opts.settings.dpi;
            var i, o, y, item;
            var dimsLeft = this.opts.state.dims.axisYLeft;
            var dimsRight = this.opts.state.dims.axisYRight;
            var dimsLines = this.opts.state.dims.axisYLines;
            var ys = this.opts.data.ys;

            this.ctx.font = 11 * dpi + 'px ' + this.opts.settings.FONTS;
            this.ctx.strokeStyle = this.isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(24, 45, 59, 0.1)';
            this.ctx.lineWidth = 1 * dpi;
            this.ctx.lineCap = 'square';
            this.ctx.lineJoin = 'square';

            for (var i in this.items) {
                item = this.items[i];

                if (item.animated) {
                    y = item.state[item.yProp];
                    o = item.state[item.oProp];
                } else {
                    y = item.y;
                    o = 1;
                }

                if (y - 6 >= 0 && y - 16 <= dimsLeft.h) {
                    this.ctx.globalAlpha = o  * (this.opts.pairY ? this.opts.state['o_0'] : 1) * opacity;
                    this.ctx.textAlign = 'left';

                    if (this.opts.pairY) {
                        this.ctx.fillStyle = this.isDarkMode ? ys[0].colors_n[1] : ys[0].colors_d[1];
                    } else {
                        this.ctx.fillStyle = this.isDarkMode ? this.opts.data.axis_n.y : this.opts.data.axis_d.y;
                    }

                    this.ctx.fillText(item.strLeft, dimsLeft.l * dpi, (y - 7) * dpi);

                    if (this.opts.pairY) {
                        this.ctx.globalAlpha = o * this.opts.state['o_1'] * opacity;
                        this.ctx.textAlign = 'right';
                        this.ctx.fillStyle = this.isDarkMode ? ys[1].colors_n[1] : ys[1].colors_d[1];

                        this.ctx.fillText(item.strRight, (dimsRight.l + dimsRight.w) * dpi, (y - 7) * dpi);
                    }
                }

                y = (y << 0) - 0.5;
                if (y >= 0 && y <= dimsLeft.h) {
                    this.ctx.beginPath();
                    this.ctx.globalAlpha = o * opacity;
                    this.ctx.moveTo(dimsLines.l * dpi, (y) * dpi);
                    this.ctx.lineTo((dimsLines.l + dimsLines.w) * dpi, (y) * dpi);
                    this.ctx.stroke();
                }
            }

            this.ctx.globalAlpha = 1;
        }
    }

    units.TAxisY = TAxisY;
})();
(function() {
    var units = window.Graph.units;

    var TBars = function(opts) {
        this.opts = opts;

        this.filteredX1 = [];
        this.filteredX2 = [];
        this.filteredJ = [];
        this.prevY = [];

        //fillrect dramatically drops fps on iphones after 5th (4s and 5 is ok, but 5s, SE, 7+ etc are freezing)
        //on direct onscreen canvas draw, so for this case we should use offscreen canvas
        this.$canvas = document.createElement('canvas');
        this.ctx = this.$canvas.getContext('2d', {alpha: false});
    }

    TBars.prototype = {
        onResize: function() {
            var dpi = this.opts.settings.dpi;
            var dims = this.opts.additional.mini ? this.opts.state.dims.mini : this.opts.state.dims.graph;
            this.$canvas.width = dims.w * dpi;
            this.$canvas.height = dims.h * dpi;
            this.cached = '';
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },

        render: function() {
            var i, j, y, o, y1, y2, xScale, yScale, xShift, yShift, xw, hBottom, yFrom;
            var opts = this.opts;
            var ys = opts.data.ys;
            var state = opts.state;
            var mini = opts.additional.mini;
            var x1 = mini ? state.xg1 : state.x1;
            var x2 = mini ? state.xg2 : state.x2;
            var settings = opts.settings;
//          var w = this.w;
//          var h = this.h;
            var pTop = settings['PADD' + (mini ? '_MINI_BAR' : '')][0];
            var pRight = settings['PADD' + (mini ? '_MINI_BAR' : '')][1];
            var pBottom = settings['PADD' + (mini ? '_MINI_BAR' : '')][2];
            var pLeft = settings['PADD' + (mini ? '_MINI_BAR' : '')][3];
            var x = opts.data.x;
            var dpi = opts.settings.dpi;
            var xInd1, xInd2;
            var ctx = this.ctx;
            var dims = mini ? state.dims.mini : state.dims.graph;
            var zoom = state.zoomMode;
            var d1 = state.detailInd1;
            var d2 = state.detailInd2;
            var zoomMorph = state.zoomMorph == undefined ? 0 : state.zoomMorph;

            var filteredX1 = this.filteredX1;
            var filteredX2 = this.filteredX2;
            var filteredJ = this.filteredJ;
            var prevY = this.prevY;

            var ysLength = ys.length;

            //cache both version version (big one is usefull for selection)
            var hash = [dims.w, dims.h, mini ? state.xg1 : state.x1, mini ? state.xg2 : state.x2, this.isDarkMode, zoom];
            if (!mini) {
                hash.push(state.y1);
                hash.push(state.y2);
            }
            for (var i = 0; i < ysLength; i++) {
                hash.push(mini ? state['om_' + i] : state['o_' + i]);
                hash.push(state['f_' + i]);
            }
            hash = hash.join(',');

            if (hash == this.cached) {
                this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
                if (mini) return;
            }

            xScale = (dims.w - pRight - pLeft) / (x2 - x1 + this.opts.data.mainPeriodLen * (1 - zoomMorph));
            xInd1 = Math.floor(units.TUtils.getXIndex(x, x1 - pLeft / xScale));
            xInd2 = Math.ceil(units.TUtils.getXIndex(x, x2 + pRight / xScale));

            if (zoom && zoomMorph == 1) {
                if (xInd1 < this.opts.state.xg1Ind) xInd1 = this.opts.state.xg1Ind;
                if (xInd2 > this.opts.state.xg2Ind) xInd2 = this.opts.state.xg2Ind - 1;
            }
            xScale *= dpi;
            xShift = (pLeft) * dpi - x1 * xScale;
            hBottom = (dims.h - pBottom) * dpi;

            var xwMain = this.opts.data.mainPeriodLen * xScale;
            var xwDetail = this.opts.data.detailPeriodLen * xScale;

            if (hash != this.cached) {
                ctx.fillStyle = this.isDarkMode ? '#242f3e' : '#fff';
                ctx.fillRect(0, 0, dims.w * dpi, dims.h * dpi);

                var xw;
                var filteredInd = 0;

                for (var j = xInd1; j <= xInd2; j++) {

                    if (zoom) {
                        if (j >= d1 && j <= d2) {
                            xw = xwDetail;
                        } else {
                            xw = xwMain;
                        }
                    } else {
                        xw = xwMain;
                    }

                    var tmpX1 = Math.round(x[j] * xScale + xShift);
                    var tmpX2 = Math.round(x[j] * xScale + xShift + xw);

                    if (tmpX2 - tmpX1 > 0) {
                        filteredX1[filteredInd] = tmpX1;
                        filteredX2[filteredInd] = tmpX2;
                        filteredJ[filteredInd] = j;
                        prevY[filteredInd] = 0;
                        filteredInd++;
                    }

                }

                for (var i = 0; i < ysLength; i++) {
                    o = mini ? state['om_' + i] : state['o_' + i];

                    if (o > 0) {
                        y = ys[i].y;
                        yFrom = ys[i].yFrom;

                        y1 = mini ? state['y1m'] : state['y1'];
                        y2 = mini ? state['y2m'] : state['y2'];

                        yScale = dpi * (dims.h - pTop - pBottom) / (y2 - y1);
                        yShift = (dims.h - pBottom) * dpi + y1 * yScale;

                        var k = o * yScale;

                        ctx.fillStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];
                        ctx.globalAlpha = state['f_' + i] * 0.9 + 0.1;

                        var yVal;

                        ctx.beginPath();
                        ctx.moveTo(Math.round(x[xInd2] * xScale + xShift + (zoomMorph == 1 ? xwDetail : xwMain)), Math.round(hBottom));

                        if (i > 0) {
                            for (var j = filteredInd - 1; j >= 0; j--) {
                                var curY = hBottom - prevY[j];
                                ctx.lineTo(filteredX2[j], Math.round(curY));
                                ctx.lineTo(filteredX1[j], Math.round(curY));
                            }
                        } else {
                            ctx.lineTo(Math.round(x[xInd1] * xScale + xShift), Math.round(hBottom));
                        }

                        for (var j = 0; j < filteredInd; j++) {
                            var jInd = filteredJ[j];
                            if (zoom) {
                                if (jInd >= d1 && jInd <= d2) {
                                    yVal = yFrom[jInd] + zoomMorph * (y[jInd] - yFrom[jInd]);
                                } else {
                                    yVal = y[jInd] + zoomMorph * (y[d1] - y[jInd]);//approximation
                                }
                            } else {
                                yVal = y[jInd];
                            }

                            yVal = yVal || 0; //absent values

                            var curY = ((yShift - yVal * k));
                            var curH = hBottom - curY;

                            prevY[j] += curH;

                            ctx.lineTo(filteredX1[j], Math.round(hBottom - prevY[j]));
                            ctx.lineTo(filteredX2[j], Math.round(hBottom - prevY[j]));
                        }

                        ctx.closePath();
                        ctx.fill();
                    }
                }

                ctx.globalAlpha = 1;

                this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
            }


            //tooltip selection
            if (state.barInd > -1 && !mini) {
                this.opts.ctx.fillStyle = this.isDarkMode ? 'rgba(36, 47, 62, 0.5)' : 'rgba(255, 255, 255, 0.5)';
                this.opts.ctx.globalAlpha = state.barO;
                this.opts.ctx.fillRect(0, 0, dims.w * dpi, dims.h * dpi);
                var yStart = 0;

                for (var i = 0; i < ysLength; i++) {
                    o = state['o_' + i];
                    if (o > 0) {
                        y = ys[i].y;
                        yFrom = ys[i].yFrom;
                        y1 = state['y1'];
                        y2 = state['y2'];
                        yScale = dpi * (dims.h - pTop - pBottom) / (y2 - y1);
                        yShift = (dims.h - pBottom) * dpi + y1 * yScale;
                        var k = o * yScale;

                        this.opts.ctx.fillStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];
                        this.opts.ctx.globalAlpha = state['f_' + i] * 0.9 + 0.1;

                        if (zoom) {
                            if (state.barInd >= d1 && state.barInd <= d2) {
                                yVal = yFrom[state.barInd] + zoomMorph * (y[state.barInd] - yFrom[state.barInd]);
                                xw = xwDetail;
                            } else {
                                xw = xwMain;
                                yVal = y[state.barInd] + zoomMorph * (y[d1] - y[state.barInd]);//approx
                            }
                        } else {
                            yVal = y[state.barInd];
                            xw = xwMain;
                        }

                        yVal = yVal || 0; //absent values

                        var yEnd = hBottom - ((yShift - yVal * k)) + yStart;

                        this.opts.ctx.fillRect(Math.round(x[state.barInd] * xScale + xShift), Math.round(hBottom - yStart + dims.t * dpi), Math.max(Math.round(xw), 1), Math.round(yStart) - Math.round(yEnd));

                        yStart = yEnd;
                    }
                }

                ctx.globalAlpha = 1;
            }



            this.cached = hash;

        }
    }

    units.TBars = TBars;
})();
(function() {
    var units = window.Graph.units;

    var TChart = function(opts) {
        this.state = {};

        this.state.masterVisibility = 1;
        this.state.slaveVisibility = 0;
        this.specialZoomTransition = undefined;
        this.darkMode = !!document.documentElement.classList.contains('dark');

        var isIEOld = ((!!window.ActiveXObject && +(/msie\s(\d+)/i.exec(navigator.userAgent)[1])) || NaN - 0) < 11;
        var isIE11 = navigator.userAgent.indexOf('Trident/') != -1 && (navigator.userAgent.indexOf('rv:') != -1 || navigator.appName.indexOf('Netscape') != -1);

        this.settings = {
            isIE: isIEOld || isIE11,
            isEdge: /Edge\/\d./i.test(navigator.userAgent),
            dpi: Math.min(window.devicePixelRatio || 1, 2),
            Y_AXIS_RANGE: 5.3, //0.3 this is part of one step, graph is overflowed above last y axis for this value, so take care of it
            PADD: [20, 16, 20, 16],
            PADD_MINI: [2, 0, 2, 0],
            PADD_MINI_BAR: [0, 0, 0, 0],
            PADD_MINI_AREA: [0, 0, 0, 0],
            Y_LABELS_WIDTH: 50,
            X_LABELS_HEIGHT: 12,
            DATES_HEIGHT: 18,
            DATES_WIDTH: 300,
            MINI_GRAPH_HEIGHT: 40,
            MINI_GRAPH_TOP: 14,
            MINI_GRAPH_BOTTOM: 2,
            FADE_HEIGHT: 16,
            PIE_RADIUS: 130,
            FONTS: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif'
        };

        this.data = {
            caption: opts.data.title,
            detailsFunc: opts.data.x_on_zoom,
            hasDetail: !!opts.data.x_on_zoom,
            slave: opts.slave,
            yTickFormatter: opts.data.yTickFormatter,
            yTooltipFormatter: opts.data.yTooltipFormatter,
            xTickFormatter: opts.data.xTickFormatter,
            xTooltipFormatter: opts.data.xTooltipFormatter,
            xRangeFormatter: opts.data.xRangeFormatter,
            strokeWidth: opts.data.strokeWidth || 'auto',
            hidden: opts.data.hidden || [],
            tooltipOnHover: !!opts.data.tooltipOnHover,
            sideLegend: opts.data.sideLegend || false,
            pieZoomRange: opts.data.pieZoomRange || 7 * 86400 * 1000,
            pieLabelsPercentages: {
                outboard: opts.data.pieLabelsPercentages && opts.data.pieLabelsPercentages.outboard != undefined ? opts.data.pieLabelsPercentages.outboard : 5,
                hoverOnly: opts.data.pieLabelsPercentages && opts.data.pieLabelsPercentages.hoverOnly != undefined ? opts.data.pieLabelsPercentages.hoverOnly : 2
            },
            subchart: {
                show: opts.data.subchart && opts.data.subchart.show != undefined ? opts.data.subchart.show : true,
                defaultZoom: opts.data.subchart && opts.data.subchart.defaultZoom
            }
        };

        if (opts.data.y_scaled) {
            this.pairY = true;
        }

        this.graphStyle = 'line';
        var settings = this.settings;

        //transform data to more convinient format
        opts.data.columns.forEach(function(item, ind) {
            var id = item.shift();
            var tp = opts.data.types[id];

            if (tp === 'x') {
                this.data.x = item;

                this.state.xCount = item.length;
                this.state.x1 = item[(item.length * 0.75) << 0]; //initial zoom if no defaultZoom set
                this.state.x2 = item[item.length - 1];
                this.state.xg1 = item[0];
                this.state.xg2 = item[item.length - 1];
                this.state.xg1Ind = 0;
                this.state.xg2Ind = item.length - 1;
                this.state.xMainMin = item[0];
                this.state.xMainMax = item[this.state.xg2Ind];
                this.state.xgMin = item[0];
                this.state.xgMax = item[this.state.xg2Ind];

                var defaultZoom = this.getDefaultZoom({
                    x1: this.state.x1,
                    x2: this.state.x2,
                    xg1: this.state.xg1,
                    xg2: this.state.xg2,
                    default: this.data.subchart.defaultZoom
                });

                this.state.x1 = defaultZoom.x1;
                this.state.x2 = defaultZoom.x2;

                this.data.mainPeriodLen = this.data.x[1] - this.data.x[0];
                this.data.detailPeriodLen = this.data.mainPeriodLen; //first detail upload would set correct value

                this.data.dates = [];
                this.data.datesShort = [];
                this.data.datesRange = [];

                var xTooltipFormatter = units.TUtils.getFormatter('xTooltipFormatter', this.data, 0);
                var xTickFormatter = units.TUtils.getFormatter('xTickFormatter', this.data, 0);
                var xRangeFormatter = units.TUtils.getFormatter('xRangeFormatter', this.data, 0);
                var maxXTickLength = 0;

                item.forEach(function(item, ind) {
                    this.data.dates[ind] = xTooltipFormatter(item, false);
                    this.data.datesShort[ind] = xTickFormatter(item, false);
                    this.data.datesRange[ind] = xRangeFormatter(item, false);

                    if (this.data.datesShort[ind].length > maxXTickLength) {
                        maxXTickLength = this.data.datesShort[ind].length;
                    }
                }.bind(this));

                this.data.maxXTickLength = maxXTickLength;
            }

            if (tp !== 'x') {
                this.data.ys = this.data.ys || [];
                this.data.yIds = this.data.yIds || {};

                var color = opts.data.colors[id];
                this.data.ys.push({
                    colors_d: [color, color, color], //light mode colors: [line/area/bar/step, switcher/y_labels(for y_scaled type), tooltip entry]
                    colors_n: [color, color, color], //dark ones
                    label: opts.data.names[id],
                    y: item,
                    tp: tp,
                    id: id
                });


                if (tp == 'line' || tp == 'step') {
                    this.data.axis_d = {
                        x: '#8E8E93',
                        y: '#8E8E93'
                    };
                    this.data.axis_n = {
                        x: 'rgba(163,177,194,0.6)',
                        y: 'rgba(163,177,194,0.6)'
                    };
                } else {
                    this.data.axis_d = {
                        x: 'rgba(37,37,41,0.5)',
                        y: 'rgba(37,37,41,0.5)'
                    };
                    this.data.axis_n = {
                        x: 'rgba(163,177,194,0.6)',
                        y: 'rgba(236,242,248,0.5)'
                    };
                }

                var yind = this.data.ys.length - 1;

                var isVisible = this.data.hidden.indexOf(id) == -1;

                this.data.yIds[id] = yind;
                this.state['e_' + yind] = isVisible;
                this.state['o_' + yind] = isVisible ? 1 : 0;
                this.state['om_' + yind] = isVisible ? 1 : 0;
                this.state['pieInd_' + yind] = 0;
                this.state['f_' + yind] = 1;

                this.graphStyle = tp;
            }
        }.bind(this));

        this.state.activeColumnsCount = this.data.ys.length;
        this.updateSpeed();

        if (this.graphStyle == 'area') {
            settings.Y_AXIS_RANGE = 4.06;
            this.data.hasDetail = true;
        };

        //reduce global range to exclude data gaps
        var reducedRange = this.reduceGlobalRange({});

        if (reducedRange.isReduced) {
            this.state.x1 = reducedRange.x1;
            this.state.x2 = reducedRange.x2;
            this.state.xg1 = reducedRange.xg1;
            this.state.xg2 = reducedRange.xg2;
            this.state.xg1Ind = reducedRange.xg1Ind;
            this.state.xg2Ind = reducedRange.xg2Ind;
        }


        this.onResize = this.onResize.bind(this);
        this.onSwitcherChange = this.onSwitcherChange.bind(this);
        this.onSwitcherEnter = this.onSwitcherEnter.bind(this);
        this.onSwitcherLeave = this.onSwitcherLeave.bind(this);
        this.onHandleMove = this.onHandleMove.bind(this);
        this.toggleZoom = this.toggleZoom.bind(this);

        this.createDOM(opts.container);

        window.addEventListener('resize', this.onResize);

        document.addEventListener('darkmode', function(e) {
            this.setDarkMode(!this.darkMode);
        }.bind(this), false);

        document.addEventListener('chart-hide-tips', function(e) {
            e.detail.except != this && this.tip.toggle(false);
        }.bind(this), false);

        this.opts = opts;

        this.onResize();

        this.darkMode && this.setDarkMode(this.darkMode);

        //detect dpi changes
        window.matchMedia('(-webkit-min-device-pixel-ratio: 1), (min-resolution: 96dpi)').addListener(this.onResize);
        window.matchMedia('(-webkit-min-device-pixel-ratio: 1.5), (min-resolution: 144dpi)').addListener(this.onResize);
        window.matchMedia('(-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi)').addListener(this.onResize);
        window.matchMedia('(-webkit-min-device-pixel-ratio: 3), (min-resolution: 288dpi)').addListener(this.onResize);
    }

    TChart.prototype = {

        reduceGlobalRange: function(params) {
            var x1 = params.x1 == undefined ? this.state.x1 : params.x1;
            var x2 = params.x2 == undefined ? this.state.x2 : params.x2;
            var xg1Orig = params.xg1 == undefined ? this.state.xg1 : params.xg1;
            var xg2Orig = params.xg2 == undefined ? this.state.xg2 : params.xg2;
            var x = params.useSaved ? this.data.saved.x : this.data.x;


            var xg1Ind = Math.floor(units.TUtils.getXIndex(x, this.state.xgMin));
            var xg2Ind = Math.ceil(units.TUtils.getXIndex(x, this.state.xgMax));

            var minXInd = xg2Ind;
            var maxXInd = xg1Ind;
            var shift = this.state.zoomMode ? (this.graphStyle == 'bar' || this.graphStyle == 'step' ? 1 : 2) : 0;

            if (this.graphStyle == 'area') shift = 0; //has no details insertion

            this.data.ys.forEach(function(item, ind) {
                var y = params.useSaved ? this.data.saved.y[ind] : item.y;
                if (this.state['e_' + ind]) {
                    for (var i = xg1Ind; i <= xg2Ind - shift; i++) {
                        var v = y[i];
                        if (v != undefined) {
                            minXInd = Math.min(minXInd, i);
                            maxXInd = Math.max(maxXInd, i);
                        }
                    }
                }
            }.bind(this));

            if (maxXInd + shift == xg2Ind) {
                maxXInd = xg2Ind - Math.max(shift - 1, 0);
            }

            if (minXInd >= maxXInd) {
                return {
                    isReduced: false
                };
            }


            var xg1 = x[minXInd];
            var xg2 = x[maxXInd];

            if (xg1Orig == xg1 && xg2Orig == xg2) {
                return {
                    isReduced: false
                };
            }

            if (x2 > xg2) {
                x1 = xg2 - (x2 - x1);
                x2 = xg2;
                if (x1 < xg1) {
                    x1 = xg1;
                }
            } else if (x1 < xg1) {
                x2 = xg1 + (x2 - x1);
                x1 = xg1;
                if (x2 > xg2) {
                    x2 = xg2;
                }
            }

            return {
                isReduced: true,
                x1: x1,
                x2: x2,
                xg1: xg1,
                xg2: xg2,
                xg1Ind: minXInd,
                xg2Ind: maxXInd,
            };
        },

        getDefaultZoom: function(params) {
            if (!params.default) {
                return {
                    x1: params.x1,
                    x2: params.x2
                };
            }

            var res = {};
            res.x1 = params.default[0];
            res.x2 = params.default[1];
            res.x1 = Math.max(res.x1, params.xg1);
            res.x2 = Math.min(res.x2, params.xg2);

            if (res.x1 >= res.x2) {
                res.x1 = params.xg1;
                res.x2 = params.xg2;
            }

            return res;
        },

        updateSpeed: function(speed) {
            var points = this.state.activeColumnsCount * this.state.xCount * Math.pow((this.state.x2 - this.state.x1) / (this.state.xMainMax - this.state.xMainMin), 0.5);
            var periods = (this.state.deviceSpeed * points / 16.66 << 0);
            var k = Math.max(1 - 0.25 * periods, 0);
            k = Math.pow(k, 0.85);
//          if (this.state.deviceSpeed == undefined) {
//              speed = 1;
//          }
            speed = 1;
            this.state.speed = speed; // speed == undefined ? k : speed;
            return this.state.speed;
        },

        getYMinMax: function(x1, x2, isMini, fitTo, useSaved) {
            if (this.graphStyle == 'area') {
                return {
                    min: 0,
                    max: 102
                };
            }

            var graphAreaWidth = this.state.dims ? this.state.dims.graph.w : this.getGraphWidth(this.data.sideLegend).width;

            var yMin = Number.MAX_VALUE;
            var yMax = -Number.MAX_VALUE;
            var datePerPixel = (x2 - x1) / graphAreaWidth;

            var start = units.TUtils.getXIndex(useSaved ? this.data.saved.x : this.data.x, x1 - datePerPixel * this.settings.PADD[3]);
            var end = units.TUtils.getXIndex(useSaved ? this.data.saved.x : this.data.x, x2 + datePerPixel * this.settings.PADD[1]);
            var i, v, yFirst, yLast, j;
            var resMin = [];
            var resMax = [];
            var settings = this.settings;
            var state = this.state;
            var prevRange;

            if (!useSaved && this.state.zoomMode) { //not backing up from zoom mode
                start = Math.max(start, this.state.detailInd1);
                end = Math.min(end, this.state.detailInd2);
            } else {
                start = Math.max(start, 0);
                end = Math.min(end, this.data.x.length - 1);
            }

            var floorStart = Math.floor(start);
            var ceilStart = Math.ceil(start);
            var floorEnd = Math.floor(end);
            var ceilEnd = Math.ceil(end);

            if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                this.data.ys.forEach(function(item, ind) {
                    var y = useSaved ? this.data.saved.y[ind] : item.y;
                    var startIndex = this.graphStyle == 'step' ? floorStart : ceilStart;
                    var endIndex = this.graphStyle == 'step' ? ceilEnd : floorEnd;

                    if (state['e_' + ind] || (ind == 0 && this.pairY)) {
                        for (var i = startIndex; i <= endIndex; i++) {
                            v = y[i];
                            if (v === undefined) continue;
                            if (v < yMin) yMin = v;
                            if (v > yMax) yMax = v;
                        }

                        if (this.graphStyle == 'line') {
                            if (y[floorStart] !== undefined && y[ceilStart] !== undefined) {
                                yFirst = y[floorStart] + (start - floorStart) * (y[ceilStart] - y[floorStart]); //clipped part of line from the beginning
                                if (yFirst < yMin) yMin = yFirst;
                                if (yFirst > yMax) yMax = yFirst;
                            }

                            if (y[floorEnd] !== undefined && y[ceilEnd] !== undefined) {
                                yLast = y[floorEnd] + (end - floorEnd) * (y[ceilEnd] - y[floorEnd]); //clipped part of line from the end
                                if (yLast < yMin) yMin = yLast;
                                if (yLast > yMax) yMax = yLast;
                            }
                        }
                    }

                    if (this.pairY) {
                        //this.prevRange will be available for pair graphs for second one to fit first one
                        //if first graph is disabled this.prevRange wiil be null and range will be rounded as always
                        var tunedY = proceedMinMax(yMin, yMax, ind, prevRange);
                        resMin[ind] = tunedY.min;
                        resMax[ind] = tunedY.max;
                        yMin = Number.MAX_VALUE;
                        yMax = -Number.MAX_VALUE;
                        prevRange = tunedY.range;
                    }
                }.bind(this));
            }

            if (this.graphStyle == 'bar') {
                var visibleCols = [];

                for (var j = 0; j < this.data.ys.length; j++) {
                    if (state['e_' + j]) {
                        visibleCols.push(j);
                    }
                }

                var colsLen = visibleCols.length;

                for (var i = floorStart; i <= ceilEnd; i++) {
                    var yCur = 0;
                    for (var j = 0; j < colsLen; j++) {
                        yCur += (useSaved ? this.data.saved.y[visibleCols[j]][i] : this.data.ys[visibleCols[j]].y[i]) || 0;
                    }
                    if (yCur > yMax) yMax = yCur;
                }

                yMin = 0;
            }

            if (this.pairY) {
                //for absent values
                if (isNaN(resMin[0])) resMin[0] = resMin[1];
                if (isNaN(resMin[1])) resMin[1] = resMin[0];
                if (isNaN(resMax[0])) resMax[0] = resMax[1];
                if (isNaN(resMax[1])) resMax[1] = resMax[0];
                return {
                    min: resMin,
                    max: resMax
                };
            } else {
                var tunedY = proceedMinMax(yMin, yMax);
                return {
                    min: tunedY.min,
                    max: tunedY.max
                };
            }

            function proceedMinMax(yMin, yMax, ind, refRange) {
                if (yMin == Number.MAX_VALUE) {
                    if (isMini) {
                        if (ind == undefined) {
                            yMin = state.y1m;
                            yMax = state.y2m;
                        } else {
                            yMin = state['y1m_' + ind];
                            yMax = state['y2m_' + ind];
                        }
                    } else {
                        if (ind == undefined) {
                            yMin = state.y1;
                            yMax = state.y2;
                        } else {
                            yMin = state['y1_' + ind];
                            yMax = state['y2_' + ind];
                        }
                    }
                } else {
                    if (this.graphStyle == 'bar') {
                        yMin = 0;
                    }

                    yMin = Math.floor(yMin);
                    yMax = Math.ceil(yMax);

                    if (fitTo) {
                        //second pair should fit it scale to first one exactly
                        //but only then first one is enabled
                        //refRange holds data to fit within
                        var range = units.TUtils.roundRange(yMin, yMax, settings.Y_AXIS_RANGE, refRange);
                        yMin = range.yMin;
                        yMax = range.yMax;
                    }
                }

                return {
                    min: yMin,
                    max: yMax,
                    range: range
                };
            }
        },


        setDarkMode: function(enabled) {
            this.darkMode = enabled;

            this.graph.setDarkMode(enabled);
            this.axisY.setDarkMode(enabled);
            this.fade.setDarkMode(enabled);
            this.axisX.setDarkMode(enabled);
            this.mini.setDarkMode(enabled);
            this.handle.setDarkMode(enabled);
            this.tip.setDarkMode(enabled);
            this.switchers.setDarkMode(enabled);
            this.composer.setDarkMode(enabled);
        },


        getGraphWidth: function(hasLegend) {
            var rectEl = this.$el.getBoundingClientRect();

            if (hasLegend) {
                var rectLegend = this.$switchers.getBoundingClientRect();

                if (rectEl.width - rectLegend.width >= 500) {
                    return {
                        hasSpaceForLegend: true,
                        width: Math.max(rectEl.width - rectLegend.width, 1),
                    };
                } else {
                    return {
                        hasSpaceForLegend: false,
                        width: rectEl.width,
                    };
                }
            } else {
                return {
                    width: rectEl.width
                };
            }
        },

        onResize: function() {
            var dpi = Math.min(window.devicePixelRatio || 1, 2);
            if (this.ww == window.innerWidth && dpi == this.settings.dpi) return;
            this.settings.dpi = dpi;

            this.ww = window.innerWidth;

            if (this.data.sideLegend) {
                this.$switchers.classList.remove('tchart--switchers__no-space');

                var graphWidthData = this.getGraphWidth(true);

                //if no space for legend - skip sideLegend option
                if (graphWidthData.hasSpaceForLegend) {
                    this.$graph.style.width = graphWidthData.width + 'px';
                } else {
                    this.$switchers.classList.add('tchart--switchers__no-space');
                    this.$graph.style.width = graphWidthData.width + 'px';
                }
            }


            var rectGraph = this.$graph.getBoundingClientRect();
            var s = this.settings;
            var graphHeight = rectGraph.height - s.DATES_HEIGHT - s.MINI_GRAPH_HEIGHT - s.MINI_GRAPH_TOP - s.MINI_GRAPH_BOTTOM;

            this.state.dims = {
                composer: {
                    w: rectGraph.width,
                    h: rectGraph.height,
                    l: 0,
                    t: 0
                },
                graph: {
                    w: rectGraph.width,
                    h: graphHeight,
                    l: 0,
                    t: s.DATES_HEIGHT
                },
                axisYLeft: {
                    w: s.Y_LABELS_WIDTH,
                    h: graphHeight,
                    l: s.PADD[3],
                    t: s.DATES_HEIGHT
                },
                axisYRight: {
                    w: s.Y_LABELS_WIDTH,
                    h: graphHeight,
                    l: rectGraph.width - s.PADD[1] - s.Y_LABELS_WIDTH,
                    t: s.DATES_HEIGHT
                },
                axisYLines: {
                    w: rectGraph.width - s.PADD[1] - s.PADD[3],
                    h: graphHeight,
                    l: s.PADD[3],
                    t: s.DATES_HEIGHT
                },
                fadeTop: {
                    w: rectGraph.width,
                    h: s.FADE_HEIGHT,
                    l: 0,
                    t: s.DATES_HEIGHT
                },
                fadeBottom: {
                    w: rectGraph.width,
                    h: s.FADE_HEIGHT,
                    l: 0,
                    t: s.DATES_HEIGHT + graphHeight - s.FADE_HEIGHT
                },
                axisX: {
                    w: rectGraph.width,
                    h: s.X_LABELS_HEIGHT,
                    l: 0,
                    t: s.DATES_HEIGHT + graphHeight - s.X_LABELS_HEIGHT
                },
                dates: {
                    w: s.DATES_WIDTH,
                    h: s.DATES_HEIGHT,
                    l: rectGraph.width - s.DATES_WIDTH - s.PADD[1],
                    t: 0
                },
                mini: {
                    w: rectGraph.width - s.PADD[1] - s.PADD[3],
                    h: s.MINI_GRAPH_HEIGHT,
                    l: s.PADD[3],
                    t: s.DATES_HEIGHT + graphHeight + s.MINI_GRAPH_TOP
                },
                handle: {
                    w: rectGraph.width - s.PADD[1] - s.PADD[3],
                    h: s.MINI_GRAPH_HEIGHT + 2,
                    l: s.PADD[3],
                    t: s.DATES_HEIGHT + graphHeight + s.MINI_GRAPH_TOP - 1
                },
                tip: {
                    w: rectGraph.width,
                    h: graphHeight,
                    l: 0,
                    t: s.DATES_HEIGHT
                }
            };

            this.graph.onResize();
            this.axisY.onResize();
            this.fade.onResize();
            this.axisX.onResize();
            this.mini.onResize();
            this.handle.onResize();
            this.tip.onResize();
            this.composer.onResize();
        },

        createDOM: function(container) {
            var rangeGraph, rangeMini, i, objs;
            var settings = this.settings;

            this.$el = document.createElement('div');
            this.$el.className = 'tchart';

            if (!this.data.subchart.show) {
                this.$el.classList.add('tchart__no-subchart');
            }

            if (this.data.slave) {
                this.$el.classList.add('tchart__slave');
            }

            this.$h1 = document.createElement('h1');
            this.$h1.className = 'tchart--header';
            this.$h1.textContent = this.data.caption;
            this.$el.appendChild(this.$h1);

            this.$zoom = document.createElement('div');
            this.$zoom.className = 'tchart--zoom';
            this.$el.appendChild(this.$zoom);

            var $zoomIcon = document.createElement('div');
            this.$zoom.appendChild($zoomIcon);
            var $zoomText = document.createElement('span');
            $zoomText.textContent = 'Zoom Out';
            this.$zoom.appendChild($zoomText);

            this.$zoom.addEventListener('click', function() {
                this.toggleZoom(false);
            }.bind(this));

            this.$graph = document.createElement('div');
            this.$graph.className = 'tchart--graph';
            this.$el.appendChild(this.$graph);

            this.$switchers = document.createElement('div');
            this.$switchers.className = 'tchart--switchers';
            this.data.sideLegend && this.$switchers.classList.add('tchart--switchers__side-legend');
            this.$el.appendChild(this.$switchers);

            container.appendChild(this.$el);

            rangeGraph = this.getYMinMax(this.state.x1, this.state.x2, false, true);
            rangeMini = this.getYMinMax(this.state.xg1, this.state.xg2, true);

            if (this.pairY) {
                for (var i = 0; i < this.data.ys.length; i++) {
                    this.state['y1_' + i] = rangeGraph.min[i];
                    this.state['y2_' + i] = rangeGraph.max[i];
                    this.state['y1m_' + i] = rangeMini.min[i];
                    this.state['y2m_' + i] = rangeMini.max[i];
                }
            } else {
                this.state['y1'] = rangeGraph.min;
                this.state['y2'] = rangeGraph.max;
                this.state['y1m'] = rangeMini.min;
                this.state['y2m'] = rangeMini.max;
            }

            this.composer = new units.TComposer({
                $parent: this.$graph,
                settings: settings,
                chart: this,
                state: this.state,
                data: this.data,
                graphStyle: this.graphStyle,
            });

            this.animator = new units.TAnimator({
                state: this.state,
                composer: this.composer
            });

            var graphStyle = {
                'line': 'TLines',
                'step': 'TLines',
                'bar': 'TBars',
                'area': 'TAreas'
            };

            objs = [
                ['graph', graphStyle[this.graphStyle], this.$graph],
                ['axisX', 'TAxisX', this.$graph],
                ['fade', 'TFade', this.$graph],
                ['axisY', 'TAxisY', this.$graph],
                ['tip', 'TTip', this.$graph, {
                    onClick: this.toggleZoom
                }],
                ['mini', graphStyle[this.graphStyle], this.$graph, {
                    mini: true
                }],
                ['handle', 'THandle', this.$graph, {
                    cb: this.onHandleMove
                }],
                ['switchers', 'TSwitchers', this.$switchers, {
                    onClick: this.onSwitcherChange,
                    onLongTap: this.onSwitcherChange,
                    onEnter: this.onSwitcherEnter,
                    onLeave: this.onSwitcherLeave,
                }],
            ];

            objs.forEach(function(obj) {
                this[obj[0]] = new units[obj[1]]({
                    animator: this.animator,
                    $canvas: this.composer.$canvas,
                    ctx: this.composer.ctx,
                    graphStyle: this.graphStyle,
                    chart: this,
                    pairY: this.pairY,
                    state: this.state,
                    data: this.data,
                    $parent: obj[2],
                    settings: settings,
                    additional: obj[3] || {}
                });
                this[obj[0]].id = obj[0];
            }.bind(this));
        },


        onHandleMove: function(x1, x2, tp, firstMove) {
            //snap to days behaviour
            // var isMagnet = this.state.zoomMode;

            this.updateSpeed();

            // isMagnet = false; //due to range reducers on detail data gaps

            /*
            if (isMagnet) {
                var periodLen = this.data.mainPeriodLen;
                x1 = Math.round(x1 / periodLen) * periodLen;
                x2 = Math.round(x2 / periodLen) * periodLen;

                var x1 = Math.min(Math.max(x1, this.state.xg1), this.state.xg2 - periodLen);
                var x2 = Math.min(Math.max(x2, this.state.xg1 + periodLen), this.state.xg2);

                if (x2 <= x1) {
                    x2 = x1 + periodLen;
                }

                var x1AnimItem = this.animator.get('x1');
                var x2AnimItem = this.animator.get('x2');
                var x1End = x1AnimItem ? x1AnimItem.end : this.state['x1'];
                var x2End = x2AnimItem ? x2AnimItem.end : this.state['x2'];

                if (x1 == x1End && x2 == x2End) {
                    return;
                }
            }
            */


            var props = [];
            var range = this.getYMinMax(x1, x2, false, true);

            this.axisX.setAnimation(tp != 'both');
            this.axisY.setAnimation(true);
            this.axisY.setForceUpdate(false);
            firstMove && units.TUtils.triggerEvent('chart-hide-tips', {
                except: null
            });

            props.push({
                prop: 'x1',
                state: this.state,
                end: x1,
                fixed: true,
                duration: 0,
                group: {
                    top: true,
                    bottom: true
                }
            });

            props.push({
                prop: 'x2',
                state: this.state,
                end: x2,
                fixed: true,
                duration: 0,
                group: {
                    top: true,
                    bottom: true
                }
            });

            for (var i = 0; i < this.data.ys.length; i++) {
                if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                    props.push({
                        prop: this.pairY ? 'y1_' + i : 'y1',
                        state: this.state,
                        end: this.pairY ? range.min[i] : range.min,
                        duration: 500,
                        fixed: true,
                        tween: 'exp',
                        speed: 0.25,
                        group: {
                            top: true
                        }
                    });
                }

                if (this.graphStyle != 'area') {
                    props.push({
                        prop: this.pairY ? 'y2_' + i : 'y2',
                        state: this.state,
                        end: this.pairY ? range.max[i] : range.max,
                        duration: 500,
                        fixed: true,
                        tween: 'exp',
                        speed: 0.25,
                        group: {
                            top: true
                        }
                    });
                }
            }

            this.animator.add(props);
        },


        onSwitcherEnter: function(ind) {
            clearTimeout(this.switcherLeaveTimeout);

            var props = [];

            for (var i = 0; i < this.data.ys.length; i++) {
                props.push({
                    prop: 'f_' + i,
                    state: this.state,
                    end: ind == i ? 1 : 0,
                    duration: 300,
                    tween: 'exp',
                    speed: 0.15,
                    group: {top: true, bottom: true}
                });

                props.push({
                    prop: 'pieInd_' + i,
                    state: this.state,
                    end: ind == i ? 1 : 0,
                    duration: 300,
                    tween: 'exp',
                    speed: 0.15,
                    group: {top: true, bottom: true}
                });
            }

            this.animator.add(props);
        },


        onSwitcherLeave: function(propsind) {
            clearTimeout(this.switcherLeaveTimeout);

            this.switcherLeaveTimeout = setTimeout(function () {
                var props = [];

                for (var i = 0; i < this.data.ys.length; i++) {
                    props.push({
                        prop: 'f_' + i,
                        state: this.state,
                        end: 1,
                        duration: 300,
                        tween: 'exp',
                        speed: 0.15,
                        group: {top: true, bottom: true}
                    });

                    props.push({
                        prop: 'pieInd_' + i,
                        state: this.state,
                        end: 0,
                        duration: 300,
                        tween: 'exp',
                        speed: 0.15,
                        group: {top: true, bottom: true}
                    });
                }

                this.animator.add(props);
            }.bind(this), 300);
        },


        onSwitcherChange: function(enabled, ind) {
            var rangeGraph, rangeMini, i, props = [],
                isCurrent, e = [],
                prevE = [];
            var longTap, isAllOffExceptCurrent;

            this.updateSpeed();

            if (typeof enabled != 'boolean') {
                longTap = true;
                ind = enabled;

                isAllOffExceptCurrent = true;
                for (var i = 0; i < this.data.ys.length; i++) {
                    isAllOffExceptCurrent = isAllOffExceptCurrent && (i == ind ? this.state['e_' + i] : !this.state['e_' + i]);
                }
            }

            var maxYSize = 0;

            for (var i = 0; i < this.data.ys.length; i++) {
                isCurrent = i == ind;

                prevE[i] = this.state['e_' + i];

                if (longTap) {
                    this.state['e_' + i] = isCurrent;
                } else if (isCurrent) {
                    this.state['e_' + i] = !!enabled;
                }

                if (isAllOffExceptCurrent && longTap) {
                    this.state['e_' + i] = true;
                }

                e[i] = this.state['e_' + i];

                if (e[i]) {
                    maxYSize = Math.max(maxYSize, this.data.ys[i].y.length);
                }
            }

            //reduce global range to exclude data gaps
            var reducedRange = this.reduceGlobalRange({});

            if (reducedRange.isReduced) {
                props.push({
                    prop: 'x1',
                    state: this.state,
                    end: reducedRange.x1,
                    duration: 333,
                    group: {
                        top: true,
                        bottom: true
                    }
                });

                props.push({
                    prop: 'x2',
                    state: this.state,
                    end: reducedRange.x2,
                    duration: 333,
                    group: {
                        top: true,
                        bottom: true
                    }
                });

                props.push({
                    prop: 'xg1',
                    state: this.state,
                    end: reducedRange.xg1,
                    duration: 333,
                    group: {
                        top: true,
                        bottom: true
                    }
                });

                props.push({
                    prop: 'xg2',
                    state: this.state,
                    end: reducedRange.xg2,
                    duration: 333,
                    group: {
                        top: true,
                        bottom: true
                    }
                });
                this.state.xg1Ind = reducedRange.xg1Ind;
                this.state.xg2Ind = reducedRange.xg2Ind;

                rangeGraph = this.getYMinMax(reducedRange.x1, reducedRange.x2, false, true);
                rangeMini = this.getYMinMax(reducedRange.xg1, reducedRange.xg2, true);
            } else {
                rangeGraph = this.getYMinMax(this.state.x1, this.state.x2, false, true);
                rangeMini = this.getYMinMax(this.state.xg1, this.state.xg2, true);
            }


            units.TUtils.triggerEvent('chart-hide-tips', {
                except: null
            });
            this.axisY.setForceUpdate(true);
            this.axisY.setAnimation(true);

            this.state.activeColumnsCount = 0;

            for (var i = 0; i < e.length; i++) {
                e[i] && this.state.activeColumnsCount++;

                if (prevE[i] != e[i]) {
                    props.push({
                        prop: 'o_' + i,
                        state: this.state,
                        end: e[i] ? 1 : 0,
                        duration: 300,
                        group: {
                            top: true
                        }
                    });

                    props.push({
                        prop: 'om_' + i,
                        state: this.state,
                        end: e[i] ? 1 : 0,
                        duration: this.graphStyle == 'line' || this.graphStyle == 'step' ? 166 : 300,
                        delay: e[i] && this.graphStyle == 'line' || this.graphStyle == 'step' ? 200 : 0,
                        tween: 'linear',
                        group: {
                            bottom: true
                        }
                    });
                }
            }

            for (var i = 0; i < (this.pairY ? e.length : 1); i++) {
                if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                    props.push({
                        prop: this.pairY ? 'y1_' + i : 'y1',
                        state: this.state,
                        end: this.pairY ? rangeGraph.min[i] : rangeGraph.min,
                        duration: this.pairY ? 0 : 333,
                        group: {
                            top: true
                        }
                    });
                }

                if (this.graphStyle != 'area') {
                    props.push({
                        prop: this.pairY ? 'y2_' + i : 'y2',
                        state: this.state,
                        end: this.pairY ? rangeGraph.max[i] : rangeGraph.max,
                        duration: this.pairY ? 0 : 333,
                        group: {
                            top: true
                        }
                    });
                }

                if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                    props.push({
                        prop: this.pairY ? 'y1m_' + i : 'y1m',
                        state: this.state,
                        end: this.pairY ? rangeMini.min[i] : rangeMini.min,
                        duration: this.pairY ? 0 : 316,
                        group: {
                            bottom: true
                        }
                    });
                }

                if (this.graphStyle != 'area') {
                    props.push({
                        prop: this.pairY ? 'y2m_' + i : 'y2m',
                        state: this.state,
                        end: this.pairY ? rangeMini.max[i] : rangeMini.max,
                        duration: this.pairY ? 0 : 316,
                        group: {
                            bottom: true
                        }
                    });
                }
            }

            this.state['y1m_hidd'] = this.state.y1m;
            this.state['y2m_hidd'] = this.state.y2m;
            this.state['y1m_show'] = rangeMini.min;
            this.state['y2m_show'] = rangeMini.max;


            this.switchers.render(e);

            this.animator.add(props);
        },


        toggleSlave: function(enabled, zoomSpecialOrigin, details, speed) {
            var props = [];

            this.updateSpeed(speed);

            if (this.state.zoomModeSlave == enabled) return;

            this.state.zoomSpecialOrigin = zoomSpecialOrigin;

            if (enabled) {
                this.state.zoomModeSlave = true;

                this.switchers.switchers.forEach(function(div, ind) {
                    div.classList.add('tchart--switcher__visible');
                    div.getElementsByTagName('span')[0].textContent = details.names[ind];
                });

                this.tip.labels.forEach(function(item, ind) {
                    item.$label.textContent = details.names[ind];
                });


                this.data.x = details.x;
                var e = [];
                for (var i = 0; i < details.y.length; i++) {
                    this.data.ys[i].y = details.y[i];

                    var isVisible = details.hidden.indexOf(this.data.ys[i].id) == -1;

                    this.state['e_' + i] = isVisible;
                    this.state['o_' + i] = isVisible ? 1 : 0;
                    this.state['om_' + i] = isVisible ? 1 : 0;
                    e[i] = isVisible;
                }

                this.switchers.render(e);

                var x1 = this.data.x[0];
                var x2 = this.data.x[this.data.x.length - 1];

                this.data.dates = [];
                this.data.datesShort = [];
                this.data.datesRange = [];

                var xTooltipFormatter = units.TUtils.getFormatter('xTooltipFormatter', this.data, 1);
                var xTickFormatter = units.TUtils.getFormatter('xTickFormatter', this.data, 1);
                var xRangeFormatter = units.TUtils.getFormatter('xRangeFormatter', this.data, 1);
                var maxXTickLength = 0;

                for (var i = 0; i < this.data.x.length; i++) {
                    this.data.dates[i] = xTooltipFormatter(this.data.x[i], true);
                    this.data.datesShort[i] = xTickFormatter(this.data.x[i], true);
                    this.data.datesRange[i] = xRangeFormatter(this.data.x[i], true);

                    if (this.data.datesShort[i].length > maxXTickLength) {
                        maxXTickLength = this.data.datesShort[i].length;
                    }
                }

                this.data.maxXTickLength = maxXTickLength;

                this.data.subchart = details.subchart;
                this.data.hidden = details.hidden;

                var defaultZoom = this.getDefaultZoom({
                    x1: x1,
                    x2: x2,
                    xg1: x1,
                    xg2: x2,
                    default: this.data.subchart.defaultZoom
                });

                this.state.x1 = defaultZoom.x1;
                this.state.x2 = defaultZoom.x2;

                this.state['xCount'] = this.data.x.length;
                this.state['xg1'] = x1;
                this.state['xg2'] = x2;
                this.state['xg1Ind'] = 0;
                this.state['xg2Ind'] = this.data.x.length - 1;
                this.state['xMainMin'] = x1;
                this.state['xMainMax'] = x2;
                this.state['xgMin'] = x1;
                this.state['xgMax'] = x2;


                //reduce global range to exclude data gaps
                var reducedRange = this.reduceGlobalRange({});

                if (reducedRange.isReduced) {
                    this.state.x1 = reducedRange.x1;
                    this.state.x2 = reducedRange.x2;
                    this.state.xg1 = reducedRange.xg1;
                    this.state.xg2 = reducedRange.xg2;
                    this.state.xg1Ind = reducedRange.xg1Ind;
                    this.state.xg2Ind = reducedRange.xg2Ind;
                }


                var rangeGraph = this.getYMinMax(this.state.x1, this.state.x2, false, true);
                var rangeMini = this.getYMinMax(x1, x2, true);

                this.state['y1'] = rangeGraph.min;
                this.state['y2'] = rangeGraph.max;
                this.state['y1m'] = rangeMini.min;
                this.state['y2m'] = rangeMini.max;

            } else {
                this.switchers.switchers.forEach(function(div, ind) {
                    div.classList.remove('tchart--switcher__visible');
                });

            }
            var durationY = 450;

            setTimeout(function() {
                if (!enabled) {
                    this.state.zoomModeSlave = false;
                }
            }.bind(this), durationY + 20);

            this.state.slaveVisibility = enabled ? 0 : 1;

            props.push({
                prop: 'slaveVisibility',
                state: this.state,
                end: enabled ? 1 : 0,
                duration: durationY,
                group: {
                    top: true,
                    bottom: true
                }
            });

            this.animator.add(props);
        },


        toggleZoomSpecial: function(enabled, dt, details) {
            var props = [];

            if (this.state.zoomModeSpecial == enabled) return;

            var speed = this.updateSpeed();

            if (enabled) {
                var scale = (this.state.x2 - this.state.x1) / (this.state.dims.graph.w - this.settings.PADD[3] - this.settings.PADD[1]);
                var lPaddInDt = this.settings.PADD[3] * scale;
                var rPaddInDt = this.settings.PADD[1] * scale;
                this.state.zoomSpecialOrigin = (dt - this.state.x1 + lPaddInDt) / (this.state.x2 - this.state.x1 + lPaddInDt + rPaddInDt);

                this.state.zoomModeSpecial = true;
                this.$h1.classList.add('tchart--header__hidden');
                this.$zoom.classList.add('tchart--zoom__visible');

                this.switchers.switchers.forEach(function(div, ind) {
                    div.classList.remove('tchart--switcher__visible');
                });

                this.slaveChart.toggleSlave(true, this.state.zoomSpecialOrigin, details, speed);
            } else {
                this.$h1.classList.remove('tchart--header__hidden');
                this.$zoom.classList.remove('tchart--zoom__visible');

                this.switchers.switchers.forEach(function(div, ind) {
                    div.classList.add('tchart--switcher__visible');
                });

                this.slaveChart.toggleSlave(false, this.state.zoomSpecialOrigin, null, speed);
            }
            var durationY = 450;

            document.body.style.pointerEvents = 'none';
            setTimeout(function() {
                if (!enabled) {
                    this.state.zoomModeSpecial = false;
                }
                document.body.style.pointerEvents = '';
            }.bind(this), durationY + 20);

            this.state.masterVisibility = enabled ? 1 : 0;

            props.push({
                prop: 'masterVisibility',
                state: this.state,
                end: enabled ? 0 : 1,
                duration: durationY,
                group: {
                    top: true,
                    bottom: true
                }
            });

            this.animator.add(props);
        },


        toggleZoom: function(enabled, dt, data) {
            // this.specialZoomTransition = true;
            if (enabled) {
                // this.specialZoomTransition = this.state.isSlowNow ? true : undefined;
                // this.specialZoomTransition = true;
            }

            if (data && this.specialZoomTransition == undefined) {
                if (data.columns.length != this.data.ys.length + 1) this.specialZoomTransition = true;
                if (!this.specialZoomTransition) {
                    data.columns.forEach(function(item, ind) {
                        var id = item[0];
                        var tp = data.types[id];
                        var label = data.names[id];
                        var ind = this.data.yIds[id];
                        if (ind != undefined) {
                            var tpOrig = this.data.ys[ind].tp;
                            var labelOrig = this.data.ys[ind].label;
                            if (tpOrig != tp || labelOrig != label) {
                                this.specialZoomTransition = true;
                            }
                        } else {
                            if (id !== 'x') {
                                this.specialZoomTransition = true;
                            }
                        }
                    }.bind(this));
                }
                if (this.specialZoomTransition == undefined) {
                    this.specialZoomTransition = false;
                }
            }

            if (this.specialZoomTransition) {
                this.data.master = true;
                this.$el.classList.add('tchart__master');

                if (!this.slaveChart) {
                    var clonedData = JSON.parse(JSON.stringify(data));
                    clonedData.yTickFormatter = data.yTickFormatter;
                    clonedData.yTooltipFormatter = data.yTooltipFormatter;
                    clonedData.xTickFormatter = data.xTickFormatter;
                    clonedData.xTooltipFormatter = data.xTooltipFormatter;
                    clonedData.xRangeFormatter = data.xRangeFormatter;
                    clonedData.sideLegend = this.data.sideLegend;
                    this.slaveChart = new units.TChart({
                        container: this.opts.container,
                        slave: true,
                        data: clonedData
                    });

                    this.slaveChart.setDarkMode(this.darkMode);
                }
            }

            if (data && !data.details) {
                data.details = {
                    y: [],
                    names: []
                };

                data.columns.forEach(function(item, ind) {
                    var id = item.shift();
                    var tp = data.types[id];
                    var label = data.names[id];

                    if (tp === 'x') {
                        data.details.x = item;
                    } else {
                        if (this.specialZoomTransition) {
                            data.details.y.push(item);
                            data.details.names.push(label);
                        } else {
                            data.details.y[this.data.yIds[id]] = item;
                        }
                    }
                }.bind(this));
            }

            if (data) {
                data.subchart = {
                    show: data.subchart && data.subchart.show != undefined ? data.subchart.show : true,
                    defaultZoom: data.subchart && data.subchart.defaultZoom
                };
                data.details.subchart = data.subchart;
                data.details.hidden = data.hidden || [];
            }


            if (this.specialZoomTransition) {
                this.toggleZoomSpecial(enabled, dt, data && data.details);
                return;
            }



            var details = data && data.details;

            var rangeGraph, rangeMini, i, props = [];

            if (this.state.zoomMode == enabled) return;

            if (enabled) {
                this.state.zoomMode = true;
                this.state.zoomDir = 1;
                this.$h1.classList.add('tchart--header__hidden');
                this.$zoom.classList.add('tchart--zoom__visible');

                this.zoomEnterSpeed = this.updateSpeed();

                //area has no data
                if (data) {
                    this.data.details = {
                        yTickFormatter: data.yTickFormatter,
                        yTooltipFormatter: data.yTooltipFormatter,
                        xTickFormatter: data.xTickFormatter,
                        xTooltipFormatter: data.xTooltipFormatter,
                        xRangeFormatter: data.xRangeFormatter,
                        subchart: data.subchart,
                        hidden: data.hidden || [],
                    };
                }

                //save
                if (!this.hasSavedData) {
                    this.data.saved = {};
                    this.data.saved.x = this.data.x.slice();
                    this.data.saved.dates = this.data.dates.slice();
                    this.data.saved.datesShort = this.data.datesShort.slice();
                    this.data.saved.datesRange = this.data.datesRange.slice();
                    this.data.saved.y = [];

                    for (var j = 0; j < this.data.ys.length; j++) {
                        this.data.saved.y[j] = this.data.ys[j].y.slice();
                    }
                    this.hasSavedData = true;
                }

                if (this.data.details && this.data.details.subchart) {
                    if (!this.data.details.subchart.show) {
                        this.$el.classList.add('tchart__no-subchart');
                    } else {
                        this.$el.classList.remove('tchart__no-subchart');
                    }
                }


                this.state.zoomSaved = {
                    x1: this.state.x1,
                    x2: this.state.x2,
                    xg1: this.state.xg1,
                    xg2: this.state.xg2,
                    xgMin: this.state.xgMin,
                    xgMax: this.state.xgMax
                };

                var periodLen = this.data.mainPeriodLen;
                var x1 = dt;
                var x2 = dt + periodLen;

                if (this.data.details) {
                    var defaultZoom = this.getDefaultZoom({
                        x1: x1,
                        x2: x2,
                        xg1: details.x[0],
                        xg2: details.x[details.x.length - 1],
                        default: this.data.details.subchart.defaultZoom
                    });

                    var x1 = defaultZoom.x1;
                    var x2 = defaultZoom.x2;
                }
                var xg1, xg2;


                if (this.graphStyle != 'area') {
                    this.data.detailPeriodLen = details.x[1] - details.x[0];

                    xg1 = details.x[0];
                    xg2 = details.x[details.x.length - 1];
                } else {
                    this.data.detailPeriodLen = periodLen;
                    var totalRange = this.data.pieZoomRange;
                    xg1 = x1 - (totalRange - periodLen) / 2;
                    xg2 = xg1 + totalRange;

                    x2 = x1 + Math.round((totalRange / 7) / periodLen) * periodLen;

                    if (xg1 < this.data.x[0]) {
                        xg1 = this.data.x[0];
                        xg2 = xg1 + totalRange;

                        if (xg2 > this.data.x[this.data.x.length - 1]) {
                            xg2 = this.data.x[this.data.x.length - 1] + periodLen;
                        }
                    } else if (xg2 > this.data.x[this.data.x.length - 1]) {
                        xg2 = this.data.x[this.data.x.length - 1] + periodLen;
                        xg1 = xg2 - totalRange;

                        if (xg1 < this.data.x[0]) {
                            xg1 = this.data.x[0];
                        }
                    }
                }

                xg1 = Math.round(xg1 / periodLen) * periodLen;
                xg2 = Math.round(xg2 / periodLen) * periodLen;


                if (this.graphStyle != 'area') {
                    this.insertDetails(xg1, xg2, details);
                }

                this.state.xgMin = xg1;
                this.state.xgMax = xg2;
            } else {
                this.updateSpeed(this.zoomEnterSpeed / (this.graphStyle == 'area' ? 2 : 1));

                this.$h1.classList.remove('tchart--header__hidden');
                this.$zoom.classList.remove('tchart--zoom__visible');

                if (this.data.details) {
                    if (!this.data.subchart.show) {
                        this.$el.classList.add('tchart__no-subchart');
                    } else {
                        this.$el.classList.remove('tchart__no-subchart');
                    }
                }

                this.state.zoomDir = -1;

                x1 = this.state.zoomSaved.x1;
                x2 = this.state.zoomSaved.x2;
                xg1 = this.state.zoomSaved.xg1;
                xg2 = this.state.zoomSaved.xg2;
                this.state.xgMin = this.state.zoomSaved.xgMin;
                this.state.xgMax = this.state.zoomSaved.xgMax;
            }

            this.axisY.setForceUpdate(true);
            this.axisY.setAnimation(true);
            this.axisX.setAnimation(true);

            var duration = 450;
            var delayMain = 0;
            var delayZoom = 0;

            if (this.graphStyle == 'area') {
                duration = 350;
                if (enabled) {
                    delayZoom = duration * 0.95;
                } else {
                    delayMain = duration * 0.95;
                }
            }


            //reduce global range to exclude data gaps
            var reducedRange = this.reduceGlobalRange({
                x1: x1,
                x2: x2,
                xg1: xg1,
                xg2: xg2,
                useSaved: !enabled,
            });

            if (reducedRange.isReduced) {
                x1 = reducedRange.x1;
                x2 = reducedRange.x2;
                xg1 = reducedRange.xg1;
                xg2 = reducedRange.xg2;
            }

            this.state.xg1Ind = Math.floor(units.TUtils.getXIndex(enabled ? this.data.x : this.data.saved.x, xg1));
            this.state.xg2Ind = Math.ceil(units.TUtils.getXIndex(enabled ? this.data.x : this.data.saved.x, xg2));

            rangeGraph = this.getYMinMax(x1, x2, false, true, !enabled);
            rangeMini = this.getYMinMax(xg1, xg2, true, false, !enabled);


            document.body.style.pointerEvents = 'none';
            setTimeout(function() {
                if (!enabled) {
                    this.state.zoomMode = false;

                    if (this.graphStyle != 'area') {
                        this.revertDetails();
                    }
                }
                document.body.style.pointerEvents = '';

                this.composer.render({
                    top: true,
                    bottom: true
                });
            }.bind(this), duration + 20 + (this.graphStyle == 'area' ? duration * 0.9 : 0));

            this.state.zoomMorph = enabled ? 0 : 1;



            props.push({
                prop: 'zoomMorph',
                state: this.state,
                end: enabled ? 1 : 0,
                duration: duration,
                delay: delayZoom,
                group: {
                    top: true,
                    bottom: true
                }
            });

            props.push({
                prop: 'x1',
                state: this.state,
                end: x1,
                delay: delayMain,
                duration: duration,
                group: {
                    top: true,
                    bottom: true
                }
            });

            props.push({
                prop: 'x2',
                state: this.state,
                end: x2,
                delay: delayMain,
                duration: duration,
                group: {
                    top: true,
                    bottom: true
                }
            });

            props.push({
                prop: 'xg1',
                state: this.state,
                end: xg1,
                delay: delayMain,
                duration: duration,
                group: {
                    top: true,
                    bottom: true
                }
            });

            props.push({
                prop: 'xg2',
                state: this.state,
                end: xg2,
                delay: delayMain,
                duration: duration,
                group: {
                    top: true,
                    bottom: true
                }
            });

            for (var i = 0; i < (this.pairY ? this.data.ys.length : 1); i++) {
                if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                    props.push({
                        prop: this.pairY ? 'y1_' + i : 'y1',
                        state: this.state,
                        end: this.pairY ? rangeGraph.min[i] : rangeGraph.min,
                        delay: delayMain,
                        duration: duration,
                        group: {
                            top: true
                        }
                    });
                }

                if (this.graphStyle != 'area') {
                    props.push({
                        prop: this.pairY ? 'y2_' + i : 'y2',
                        state: this.state,
                        end: this.pairY ? rangeGraph.max[i] : rangeGraph.max,
                        delay: delayMain,
                        duration: duration,
                        group: {
                            top: true
                        }
                    });
                }

                if (this.graphStyle == 'line' || this.graphStyle == 'step') {
                    props.push({
                        prop: this.pairY ? 'y1m_' + i : 'y1m',
                        state: this.state,
                        end: this.pairY ? rangeMini.min[i] : rangeMini.min,
                        delay: delayMain,
                        duration: duration,
                        group: {
                            bottom: true
                        }
                    });
                }

                if (this.graphStyle != 'area') {
                    props.push({
                        prop: this.pairY ? 'y2m_' + i : 'y2m',
                        state: this.state,
                        end: this.pairY ? rangeMini.max[i] : rangeMini.max,
                        delay: delayMain,
                        duration: duration,
                        group: {
                            bottom: true
                        }
                    });
                }
            }

            this.animator.add(props);
        },


        insertDetails: function(xg1, xg2, details) {
            var startMain = Math.ceil(units.TUtils.getXIndex(this.data.x, xg1));
            var endMain = Math.ceil(units.TUtils.getXIndex(this.data.x, xg2));
            var startDetail = 0;
            var endDetail = details.x.length - 1;

            if (xg2 > this.data.x[endMain]) {
                endMain++;
            }


            var xl1 = 0;
            var xl2 = startMain; //not including it

            var xr1 = endMain - (this.graphStyle == 'bar' || this.graphStyle == 'step' ? 1 : 0); //not including it (for bars shoudl include)
            var xr2 = this.data.x.length - 1;


            var newX = [];
            var newDates = [];
            var newDatesShort = [];
            var newDatesRange = [];
            var i, newInd, y, yFrom, origY, origYDet;
            var newY = [];
            var newYFrom = [];

            for (var i = xl1; i < xl2; i++) {
                newInd = i - xl1;
                newX[newInd] = this.data.x[i];
                newDates[newInd] = this.data.dates[i];
                newDatesShort[newInd] = this.data.datesShort[i];
                newDatesRange[newInd] = this.data.datesRange[i];
            }
            for (var j = 0; j < this.data.ys.length; j++) {
                newY[j] = newY[j] || [];
                y = newY[j];
                origY = this.data.ys[j].y;
                for (var i = xl1; i < xl2; i++) {
                    y[i - xl1] = origY[i];
                }
            }



            //insert details
            var ndx = [];
            var fdx = [];
            var cdx = [];

            var xTooltipFormatter = units.TUtils.getFormatter('xTooltipFormatter', this.data, 1);
            var xTickFormatter = units.TUtils.getFormatter('xTickFormatter', this.data, 1);
            var xRangeFormatter = units.TUtils.getFormatter('xRangeFormatter', this.data, 1);
            var maxXTickLength = 0;

            for (var i = startDetail; i <= endDetail; i++) {
                newInd = i - startDetail + xl2;
                newX[newInd] = details.x[i];

                newDates[newInd] = xTooltipFormatter(newX[newInd], true);
                newDatesShort[newInd] = xTickFormatter(newX[newInd], true);
                newDatesRange[newInd] = xRangeFormatter(newX[newInd], true);

                if (newDatesShort[newInd].length > maxXTickLength) {
                    maxXTickLength = newDatesShort[newInd].length;
                }

                var dx = units.TUtils.getXIndex(this.data.x, newX[newInd]);
                ndx[i] = dx;
                fdx[i] = Math.floor(dx);
                cdx[i] = Math.ceil(dx);
            }

            this.data.details.maxXTickLength = maxXTickLength;

            for (var j = 0; j < this.data.ys.length; j++) {
                newY[j] = newY[j] || [];
                y = newY[j];
                origY = this.data.ys[j].y;
                origYDet = details.y[j];
                newYFrom[j] = newYFrom[j] || [];
                yFrom = newYFrom[j];
                for (var i = startDetail; i <= endDetail; i++) {
                    if (this.graphStyle == 'bar') {
                        var yInter = origY[fdx[i]] || 0;
                    } else if (this.graphStyle == 'step') {
                        var yInter = origY[fdx[i]];
                    } else {
                        var yInter = origY[fdx[i]] + (ndx[i] - fdx[i]) * (origY[cdx[i]] - origY[fdx[i]]);
                    }
                    var yDet = origYDet[i];
                    //for bars it is simple, we may use 0 as value, cause they are cumulative and always starts from 0
                    if (this.graphStyle !== 'bar') {
                        //has no initial value, but has detail value
                        if (isNaN(yInter) && !isNaN(yDet)) {
                            yInter = 0; //ugly, but seem like this situation is impossible in real cases
                        }
                    }

                    y[i - startDetail + xl2] = yDet;
                    yFrom[i - startDetail + xl2] = yInter;
                }
            }


            for (var i = xr1 + 1; i <= xr2; i++) {
                newInd = i - xr1 + endDetail + xl2;
                newX[newInd] = this.data.x[i];
                newDates[newInd] = this.data.dates[i];
                newDatesShort[newInd] = this.data.datesShort[i];
                newDatesRange[newInd] = this.data.datesRange[i];
            }
            for (var j = 0; j < this.data.ys.length; j++) {
                newY[j] = newY[j] || [];
                y = newY[j];
                origY = this.data.ys[j].y;
                for (var i = xr1 + 1; i <= xr2; i++) {
                    y[i - xr1 + endDetail + xl2] = origY[i];
                }
            }

            this.state.detailInd1 = xl2;
            this.state.detailInd2 = xl2 + endDetail - startDetail;


            this.data.x = newX;
            this.data.dates = newDates;
            this.data.datesShort = newDatesShort;
            this.data.datesRange = newDatesRange;

            for (var j = 0; j < this.data.ys.length; j++) {
                this.data.ys[j].y = newY[j];
                this.data.ys[j].yFrom = newYFrom[j];
            }
        },


        revertDetails: function() {
            this.data.x = this.data.saved.x;
            this.data.dates = this.data.saved.dates;
            this.data.datesShort = this.data.saved.datesShort;
            this.data.datesRange = this.data.saved.datesRange;

            for (var i = 0; i < this.data.ys.length; i++) {
                this.data.ys[i].y = this.data.saved.y[i];
            }
        }
    }

    units.TChart = TChart;
})();

(function() {
    var units = window.Graph.units;

    var TComposer = function(opts) {
        this.opts = opts;

        this.$canvas = document.createElement('canvas');
        this.$canvas.className = 'tchart--graph-canvas';
        this.ctx = this.$canvas.getContext('2d');
        opts.$parent.appendChild(this.$canvas);
        this.deviceSpeed = undefined;
    }

    TComposer.prototype = {
        onResize: function() {
            var dpi = this.opts.settings.dpi;
            var dims = this.opts.state.dims.composer;

            this.$canvas.width = dims.w * dpi;
            this.$canvas.height = dims.h * dpi;

            this.render({top: true, bottom: true});
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;

            //full reset canvas due to clearing areas optimizations
            this.onResize();
        },

        render: function(groups) {

            if (this.deviceSpeed == undefined) {
                var t1 = performance.now();

                var prevX = this.opts.state.x1;
                this.opts.state.x1 = this.opts.state.xMainMin;
                this.renderInner(groups);
                var t2 = performance.now();

                this.opts.state.x1 = prevX;
            }
            this.renderInner(groups);

            if (this.deviceSpeed == undefined) {
                var k = this.opts.graphStyle == 'line' || this.opts.graphStyle == 'step' ? 1.5 : 2;
                var duration = (t2 - t1) / k;
                this.deviceSpeed = duration / (this.opts.data.x.length * this.opts.data.ys.length);
                this.opts.state.deviceSpeed = this.deviceSpeed;
            }
        },

        renderInner: function(groups) {
            var dims = this.opts.state.dims;
            var state = this.opts.state;
            var ctx = this.ctx;
            var dpi = this.opts.settings.dpi;
            var settings = this.opts.settings;
            var padd = settings.PADD;
            var zoomMorph = state.zoomMorph == undefined ? 0 : state.zoomMorph;
            var pieChartAnimating = this.opts.graphStyle == 'area' && state.zoomMode && zoomMorph < 1;
            var pieChartAnimated = this.opts.graphStyle == 'area' && state.zoomMode && zoomMorph == 1;


            if (this.opts.data.master) {
                this.$canvas.style.opacity = state.masterVisibility;
            }

            if (this.opts.data.slave) {
                this.$canvas.style.opacity = state.slaveVisibility;

                this.opts.chart.$el.style.visibility = state.slaveVisibility > 0 ? 'visible' : 'hidden';
            }

            if (groups.top) {

                ctx.clearRect(dims.dates.l * dpi, dims.dates.t * dpi, dims.dates.w * dpi, dims.dates.h * dpi);

                if (this.opts.graphStyle == 'line' || this.opts.graphStyle == 'step' || (this.opts.graphStyle == 'area' && zoomMorph > 0) || (this.opts.data.slave && state.slaveVisibility < 1) || (this.opts.data.master && state.masterVisibility < 1)) {
                    if (pieChartAnimated) {
                        //for pie char need a little bit mode region to clear, cause of outboard labels
                        ctx.clearRect(dims.graph.l * dpi, (dims.graph.t - 18) * dpi, dims.graph.w * dpi, (dims.graph.h + 30) * dpi);
                    } else {
                        ctx.clearRect(dims.graph.l * dpi, dims.graph.t * dpi, dims.graph.w * dpi, dims.graph.h * dpi);
                    }
                }

                //haaack, clear only edges
                if ((this.opts.graphStyle == 'area' && zoomMorph == 0) || (this.opts.graphStyle == 'bar')) {
                    ctx.clearRect(dims.graph.l * dpi, dims.graph.t * dpi, dims.graph.w * dpi, (settings.PADD[0] + 4) * dpi);
                    ctx.clearRect(dims.graph.l * dpi, (dims.graph.t + padd[0]) * dpi, padd[3] * dpi, (dims.graph.h - padd[0] - padd[2]) * dpi);
                    ctx.clearRect(dims.graph.l * dpi, (dims.graph.t + dims.graph.h - padd[2]) * dpi, dims.graph.w * dpi, (padd[2]) * dpi);
                    ctx.clearRect((dims.graph.l + dims.graph.w - padd[1] - 1) * dpi, (dims.graph.t + padd[0]) * dpi, (padd[1] + 1) * dpi, (dims.graph.h - padd[0] - padd[2]) * dpi);
                }

                if (!pieChartAnimating && !pieChartAnimated) {
                    ctx.save();
                    ctx.beginPath();
                    ctx.rect(dims.graph.l * dpi, dims.graph.t * dpi, dims.graph.w * dpi, dims.graph.h * dpi);
                    ctx.clip();
                }

                if (this.opts.data.master && state.masterVisibility < 1) {
                    ctx.save();
                    var scale = (1 - state.masterVisibility) *5+ 1;
                    ctx.translate(dims.graph.w  * state.zoomSpecialOrigin * (1 - scale), 0);
                    ctx.scale(scale, 1);
                }

                if (this.opts.data.slave && state.slaveVisibility < 1) {
                    ctx.save();
                    var scale = state.slaveVisibility;
                    ctx.translate(dims.graph.w  * state.zoomSpecialOrigin * (1 - scale) , 0);
                    ctx.scale(scale, 1);
                }

                var opacity = 1;
                if (this.opts.graphStyle == 'area' && state.zoomMode) {
                    opacity = 1 - zoomMorph;
                }

                if (pieChartAnimating) {
                    ctx.save();

                    var r = settings.PIE_RADIUS;
                    var cw = dims.graph.w + zoomMorph * (r * 2 - dims.graph.w);
                    var ch = dims.graph.h - 42 + zoomMorph * (r * 2 - dims.graph.h + 42);

                    units.TUtils.drawRoundedRect2(
                        ctx,
                        dpi,
                        cw,
                        ch,
                        (dims.graph.w - cw) / 2 + dims.graph.l,
                        (dims.graph.h - 42 - ch) / 2 + dims.graph.t + 23,
                        zoomMorph * r
                    );

                    ctx.clip();
                }

                this.opts.chart.graph.render();

                if (pieChartAnimating) {
                    ctx.restore();
                }

                if (this.opts.data.master && state.masterVisibility < 1) {
                    ctx.restore();
                }

                if (this.opts.data.slave && state.slaveVisibility < 1) {
                    ctx.restore();
                }

                this.opts.chart.axisY.render(opacity);
                this.opts.chart.fade.render();

                if (!pieChartAnimating && !pieChartAnimated) {
                    ctx.restore();
                }

                this.opts.chart.axisX.render(opacity);
            }


            if (groups.bottom) {
                ctx.clearRect((dims.graph.l) * dpi, (dims.handle.t - 1) * dpi, (dims.graph.w) * dpi, (dims.handle.h + 2) * dpi);

                var subchartShown = this.opts.data.subchart.show;
                var isNotSpecialAndChangedSubchart = !this.opts.data.master && !this.opts.data.slave && this.opts.data.details && this.opts.data.subchart.show != this.opts.data.details.subchart.show;

                if (isNotSpecialAndChangedSubchart) {
                    if (subchartShown) {
                        subchartShown = zoomMorph < 1;
                    } else {
                        subchartShown = zoomMorph > 0;
                    }
                }

                if (subchartShown) {
                    ctx.save();
                    units.TUtils.drawRoundedRect(ctx, dpi, dims.mini.w, dims.mini.h, dims.mini.l, dims.mini.t, 7);
                    ctx.clip();
                    this.opts.chart.mini.render();
                    ctx.restore();

                    this.opts.chart.handle.render();
                }

                if (isNotSpecialAndChangedSubchart) {
                    if (zoomMorph > 0 && zoomMorph < 1) {
                        ctx.fillStyle = this.isDarkMode ? '#242f3e' : '#fff';
                        ctx.globalAlpha = this.opts.data.subchart.show ? zoomMorph : 1 - zoomMorph;
                        ctx.fillRect((dims.graph.l) * dpi, (dims.handle.t - 1) * dpi, (dims.graph.w) * dpi, (dims.handle.h + 2) * dpi);
                        ctx.globalAlpha = 1;
                    }
                }
            }
        }
    }

    units.TComposer = TComposer;
})();
(function() {
    var units = window.Graph.units;

    var TDrag = function(opts) {
        this.opts = opts;

        this.onDragStart = this.onDragStart.bind(this);
        this.onDragMove = this.onDragMove.bind(this);
        this.onDragEnd = this.onDragEnd.bind(this);
        this.isTouch = units.TUtils.isTouchDevice();

        this.skipMoveEnd = true;

        var $global = opts.useElForMove ? opts.$el : window;

        opts.$el.addEventListener(this.isTouch ? 'touchstart' : 'mousedown', this.onDragStart, {
            passive: false
        });
        $global.addEventListener(this.isTouch ? 'touchmove' : 'mousemove', this.onDragMove, {
            passive: false
        });
        $global.addEventListener(this.isTouch ? 'touchend' : 'mouseup', this.onDragEnd, {
            passive: false
        });
    }

    TDrag.prototype = {

        onDragStart: function(e) {
            this.skipMoveEnd = true;

            clearTimeout(this.pointerTimeout);

            if (this.isTouch) {
                if (e.touches.length > 1) return;
            }

            this.scroll = undefined;
            this.x = this.isTouch ? e.touches[0].pageX : e.pageX;
            this.y = this.isTouch ? e.touches[0].pageY : e.pageY;
            this.dX = 0;
            this.dY = 0;
            this.pageX = this.x;
            this.pageY = this.y;
            delete this.prevDx;
            delete this.prevDy;

            var cancelDrag = this.opts.onDragStart({
                pageX: this.x,
                pageY: this.y,
                isTouch: this.isTouch
            });

            if (cancelDrag) return;

            this.skipMoveEnd = false;
        },


        onDragMove: function(e) {
            if (this.skipMoveEnd) return;

            if (this.scroll == 'v') {
                return;
            }

            var x = this.isTouch ? e.touches[0].pageX : e.pageX;
            var y = this.isTouch ? e.touches[0].pageY : e.pageY;

            this.dX = x - this.x;
            this.dY = y - this.y;

            this.pageX = x;
            this.pageY = y;

            if (this.isTouch) {
                if (this.scroll == 'h') {
                    !this.opts.noPrevent && e.preventDefault();
                } else if (Math.abs(this.dX) > 5 || Math.abs(this.dY) > 5) {
                    this.scroll = Math.abs(this.dX) > Math.abs(this.dY) ? 'h' : 'v';
                }
            }

            if (this.prevDx != this.dX || this.prevDy != this.dY) {

                this.opts.onDragMove && this.opts.onDragMove({
                    canceled: this.scroll == 'v',
                    d: this.dX,
                    pageX: this.pageX,
                    pageY: this.pageY,
                    isTouch: this.isTouch
                });

                this.prevDx = this.dX;
                this.prevDy = this.dY;
            }
        },


        onDragEnd: function(e) {
            if (this.skipMoveEnd) return;

            this.skipMoveEnd = true;

            this.opts.onDragEnd && this.opts.onDragEnd({
                isTouch: this.isTouch,
                e: e
            });
        }
    };

    units.TDrag = TDrag;
})();
(function() {
    var TFade = function(opts) {
        this.opts = opts;
        this.ctx = opts.ctx;

        if (this.opts.graphStyle != 'area') {
            this.$fadeTop = document.createElement('canvas');
            this.ctxFadeTop = this.$fadeTop.getContext('2d');

            if (this.opts.graphStyle != 'bar') {
                this.$fadeBottom = document.createElement('canvas');
                this.ctxFadeBottom = this.$fadeBottom.getContext('2d');
            }
        }
    }

    TFade.prototype = {
        onResize: function() {
            var dpi = this.opts.settings.dpi;
            var dimsTop = this.opts.state.dims.fadeTop;
            var dimsBottom = this.opts.state.dims.fadeBottom;

            if (this.opts.graphStyle != 'area') {
                var gradientTop = this.ctxFadeTop.createLinearGradient(0, 0, 0, dimsTop.h * dpi);
                if (this.isDarkMode) {
                    gradientTop.addColorStop(0, 'rgba(36,47,62,1)');
                    gradientTop.addColorStop(1, 'rgba(36,47,62,0)');
                } else {
                    gradientTop.addColorStop(0, 'rgba(255,255,255,1)');
                    gradientTop.addColorStop(1, 'rgba(255,255,255,0)');
                }
                this.$fadeTop.width = dimsTop.w * dpi;
                this.$fadeTop.height = dimsTop.h * dpi;
                this.ctxFadeTop.fillStyle = gradientTop;
                this.ctxFadeTop.fillRect(0, 0, dimsTop.w * dpi, dimsTop.h * dpi);

                if (this.opts.graphStyle != 'bar') {
                    var gradientBottom = this.ctxFadeBottom.createLinearGradient(0, 0, 0, dimsBottom.h * dpi);
                    if (this.isDarkMode) {
                        gradientBottom.addColorStop(0, 'rgba(36,47,62,0)');
                        gradientBottom.addColorStop(1, 'rgba(36,47,62,1)');
                    } else {
                        gradientBottom.addColorStop(0, 'rgba(255,255,255,0)');
                        gradientBottom.addColorStop(1, 'rgba(255,255,255,1)');
                    }
                    this.$fadeBottom.width = dimsBottom.w * dpi;
                    this.$fadeBottom.height = dimsBottom.h * dpi;
                    this.ctxFadeBottom.fillStyle = gradientBottom;
                    this.ctxFadeBottom.fillRect(0, 0, dimsBottom.w * dpi, dimsBottom.h * dpi);
                }
            }
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
            this.onResize();
        },

        render: function() {
            var dpi = this.opts.settings.dpi;
            var dimsTop = this.opts.state.dims.fadeTop;
            var dimsBottom = this.opts.state.dims.fadeBottom;

            this.$fadeTop && this.ctx.drawImage(this.$fadeTop, dimsTop.l * dpi, dimsTop.t * dpi);
            this.$fadeBottom && this.ctx.drawImage(this.$fadeBottom, dimsBottom.l * dpi, dimsBottom.t * dpi);
        }
    }

    window.Graph.units.TFade = TFade;
})();
(function() {
    var units = window.Graph.units;

    var THandle = function(opts) {
        this.opts = opts;
        this.ctx = opts.ctx;

        this.isTouch = units.TUtils.isTouchDevice();
        this.$canvas = opts.$canvas;

        this.drag = new units.TDrag({
            $el: this.$canvas,
            onDragStart: function(params) {
                this.canvasPos = units.TUtils.getElemPagePos(this.$canvas);
                var dx = params.pageX - this.canvasPos.x;
                var dy = params.pageY - this.canvasPos.y;
                this._x1 = opts.state.x1;
                this._x2 = opts.state.x2;
                this.constrainHandleSize(false);
                this.tp = this.getTp(dx - opts.settings.PADD[3], dy - (this.opts.state.dims.composer.h - opts.settings.MINI_GRAPH_HEIGHT - opts.settings.MINI_GRAPH_BOTTOM), params.isTouch);
                this.firstMove = true;
                return !this.tp;
            }.bind(this),
            onDragMove: function(params) {
                this.onDragMove(params.d);
                this.firstMove = false;
            }.bind(this),
            onDragEnd: function(params) {
            }.bind(this)
        });

        this.onMouseMove = this.onMouseMove.bind(this);
        this.onMouseLeave = this.onMouseLeave.bind(this);

        this.trackMouse(true);
    }

    THandle.prototype = {
        getTp: function(x, y, isTouch) {
            var dims = this.opts.state.dims.handle;
            var state = this.opts.state;
            var zoomMode = this.opts.state.zoomMode;

            if (y < 0 || y > dims.h) return '';

            var xw = isTouch ? dims.w * 0.3 : 10;
            if (isTouch && xw < 14) xw = 14;
            if (isTouch && xw > 30) xw = 30;

            var xl1 = this.prevX1 + (isTouch ? (state.x1 == state.xg1 ? -5 : -15) : 0);
            var xl2 = xl1 + xw;

            var xr2 = this.prevX2 + (isTouch ? (state.x2 == state.xg2 ? 5 : 15) : 0);
            var xr1 = xr2 - xw;

            //for min handle and on extreme points
            //left hide appropriate resize handle, thus it would be easier to user to catch and move handle
            if (Math.abs(state.x2 - state.x1 - (zoomMode ? this.opts.data.mainPeriodLen : this.minRange)) < 0.01) {
                if (state.x2 == state.xg2) {
                    xr1 = xr2 + 1;
                }
                 if (state.x1 == state.xg1) {
                    xl2 = xl1 - 1;
                }
            }


            if (x > xl2 && x < xr1) {
                return 'both';
            }

            if (x >= xl1 && x <= xl2) {
                return 'start';
            }

            if (x >= xr1 && x <= xr2) {
                return 'end';
            }

            return '';
        },

        trackMouse: function(enabled) {
            if (this.isTouch) return;

            this.$canvas.addEventListener('mousemove', this.onMouseMove);
            this.$canvas.addEventListener('mouseleave', this.onMouseLeave);
        },

        onMouseLeave: function() {
            this.$canvas.classList.remove('tchart--graph-canvas__handle-pointer');
            this.$canvas.classList.remove('tchart--graph-canvas__handle-grab');
            this.$canvas.classList.remove('tchart--graph-canvas__handle-col-resize');
            delete this.canvasPos;
        },

        onMouseMove: function(e) {
            this.canvasPos = this.canvasPos || units.TUtils.getElemPagePos(this.$canvas);
            var dx = e.pageX - this.canvasPos.x;
            var dy = e.pageY - this.canvasPos.y;
            var tp = this.getTp(dx - this.opts.settings.PADD[3], dy - (this.opts.state.dims.composer.h - this.opts.settings.MINI_GRAPH_HEIGHT - this.opts.settings.MINI_GRAPH_BOTTOM), false);

            var cursors = {
                '': '',
                'both': this.opts.settings.isIE ? 'pointer' : 'grab',
                'start': 'col-resize',
                'end': 'col-resize',
            }

            this.onMouseLeave();
            cursors[tp] && this.$canvas.classList.add('tchart--graph-canvas__handle-' + cursors[tp]);
        },

        onResize: function(rect) {
            this.constrainHandleSize(true);
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },

        constrainHandleSize: function(updateHandle) {
            var dims = this.opts.state.dims.handle;
            var xScale = dims.w / (this.opts.state.xg2 - this.opts.state.xg1);
            var minRange = 32 / xScale; //45 min handle width
            var x1 = this.opts.state.x1;
            var x2 = this.opts.state.x2;
//          var xg1 = this.opts.state.xg1;
            var xg2 = this.opts.state.xg2;

            this.minRange = minRange;

            if (x2 - x1 < minRange) {
                x2 = x1 + minRange;

                if (x2 > xg2) {
                    x2 = xg2;
                    x1 = x2 - minRange;
                }

                updateHandle && this.opts.additional.cb(x1, x2, 'constraint');
            }
        },


        onDragMove: function(d) {
            var dims = this.opts.state.dims.handle;
            var tp = this.tp;
            var state = this.opts.state;
            var per = (d / dims.w) * (state.xg2 - state.xg1);
            var x1, x2;
            var _x1 = this._x1;
            var _x2 = this._x2;

            if (tp == 'both') {
                x1 = _x1 + per;
                x2 = _x2 + per;
                if (x1 < state.xg1) {
                    x1 = state.xg1;
                    x2 = state.xg1 + _x2 - _x1;
                }
                if (x2 > state.xg2) {
                    x1 = state.xg2 - (_x2 - _x1);
                    x2 = state.xg2;
                }
            }

            if (tp == 'start') {
                x2 = state.x2;
                x1 = Math.min(Math.max(_x1 + per, state.xg1), x2 - this.minRange);
            }

            if (tp == 'end') {
                x1 = state.x1;
                x2 = Math.max(Math.min(_x2 + per, state.xg2), x1 + this.minRange);
            }

            if (state.x1 == x1 && state.x2 == x2) return;

            this.opts.additional.cb(x1, x2, tp, this.firstMove);
        },


        render: function() {
            var dims = this.opts.state.dims.handle;
            var dpi = this.opts.settings.dpi;
            var state = this.opts.state;
            var xScale = 1 / (state.xg2 - state.xg1);
            var x1 = Math.round((state.x1 - state.xg1) * xScale * dims.w);
            var x2 = Math.round((state.x2 - state.xg1) * xScale * dims.w);
            var ctx = this.ctx;

            ctx.fillStyle = this.isDarkMode ? 'rgba(48, 66, 89, 0.6)' : 'rgba(226, 238, 249, 0.6)';
            units.TUtils.drawRoundedRect(ctx, dpi, x1 + 4, dims.h - 2, dims.l, dims.t + 1, [7, 0, 0, 7]);
            ctx.fill();
            units.TUtils.drawRoundedRect(ctx, dpi, dims.w - x2 + 4, dims.h - 2, dims.l + x2 - 4, dims.t + 1, [0, 7, 7, 0]);
            ctx.fill();

            if (!this.isDarkMode && this.opts.graphStyle != 'line' && this.opts.graphStyle != 'step') {
                ctx.fillStyle = '#fff';
                units.TUtils.drawRoundedRect(ctx, dpi, 12, dims.h + 2, dims.l + x1 - 1, dims.t - 1, [8, 0, 0, 8]);
                ctx.fill();
                units.TUtils.drawRoundedRect(ctx, dpi, 12, dims.h + 2, dims.l + x2 - 11, dims.t - 1, [0, 8, 8, 0]);
                ctx.fill();
            }

            ctx.fillStyle = this.isDarkMode ? '#56626D' : '#C0D1E1';
            units.TUtils.drawRoundedRect(ctx, dpi, 10, dims.h, dims.l + x1, dims.t, [7, 0, 0, 7]);
            ctx.fill();
            units.TUtils.drawRoundedRect(ctx, dpi, 10, dims.h, dims.l + x2 - 10, dims.t, [0, 7, 7, 0]);
            ctx.fill();

            ctx.fillRect((dims.l + x1 + 10) * dpi, (dims.t) * dpi, (x2 - x1 - 20) * dpi, dpi);
            ctx.fillRect((dims.l + x1 + 10) * dpi, (dims.t + dims.h - 1) * dpi, (x2 - x1 - 20) * dpi, dpi);


            ctx.strokeStyle = '#fff';
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.lineWidth = 2 * dpi;
            ctx.beginPath();
            ctx.moveTo((dims.l + x1 + 5) * dpi, (dims.t + 17) * dpi);
            ctx.lineTo((dims.l + x1 + 5) * dpi, (dims.t + 25) * dpi);
            ctx.moveTo((dims.l + x2 - 5) * dpi, (dims.t + 17) * dpi);
            ctx.lineTo((dims.l + x2 - 5) * dpi, (dims.t + 25) * dpi);
            ctx.stroke();

            this.prevX1 = x1;
            this.prevX2 = x2;
        }
    }

    units.THandle = THandle;
})();
(function() {
    var units = window.Graph.units;

    var TLines = function(opts) {
        this.opts = opts;

        this.$canvas = document.createElement('canvas');
        this.ctx = this.$canvas.getContext('2d', {alpha: true});
    }

    TLines.prototype = {
        onResize: function() {
            var dpi = this.opts.settings.dpi;
            var dims = this.opts.additional.mini ? this.opts.state.dims.mini : this.opts.state.dims.graph;
            this.$canvas.width = dims.w * dpi;
            this.$canvas.height = dims.h * dpi;
            this.cached = '';
            this.ctx.fillStyle = this.isDarkMode ? '#242f3e' : '#fff';
            this.ctx.fillRect(0, 0, dims.w * dpi, dims.h * dpi);
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
        },

        render: function() {
            var i, j, y, o, y1, y2, xScale, yScale, xShift, yShift, e, yFrom;
            var opts = this.opts;
            var ys = opts.data.ys;
            var state = opts.state;
            var mini = opts.additional.mini;
            var toCache = mini || ((opts.data.master && state.masterVisibility < 1 && state.masterVisibility > 0) || (opts.data.slave && state.slaveVisibility < 1 && state.slaveVisibility > 0));
            var x1 = mini ? state.xg1 : state.x1;
            var x2 = mini ? state.xg2 : state.x2;
            var settings = opts.settings;
            var pTop = settings['PADD' + (mini ? '_MINI' : '')][0];
            var pRight = settings['PADD' + (mini ? '_MINI' : '')][1];
            var pBottom = settings['PADD' + (mini ? '_MINI' : '')][2];
            var pLeft = settings['PADD' + (mini ? '_MINI' : '')][3];
            var x = opts.data.x;
            var dpi = opts.settings.dpi;
            var xInd1, xInd2;
            var ctx = toCache ? this.ctx : this.opts.ctx;
            var dims = mini ? state.dims.mini : state.dims.graph;
            var zoom = state.zoomMode;
            var d1 = state.detailInd1;
            var d2 = state.detailInd2;
            var morph = state.zoomMorph == undefined ? 0 : state.zoomMorph;
            var ysLen = ys.length;
            var isStepMode = opts.graphStyle == 'step';

            xScale = (dims.w - pRight - pLeft) / (x2 - x1 + (isStepMode ? this.opts.data.mainPeriodLen * (1 - morph) : 0));
            xInd1 = Math.floor(units.TUtils.getXIndex(x, x1 - pLeft / xScale));
            xInd2 = Math.ceil(units.TUtils.getXIndex(x, x2 + pRight / xScale));
            xScale *= dpi;
            xShift = (pLeft + (toCache ? 0 : dims.l)) * dpi - x1 * xScale;

            if (isStepMode && zoom && morph == 1) {
                if (xInd1 < this.opts.state.xg1Ind) xInd1 = this.opts.state.xg1Ind;
                if (xInd2 > this.opts.state.xg2Ind) xInd2 = this.opts.state.xg2Ind - 1;
            }

            var xw;
            var xwMain = this.opts.data.mainPeriodLen * xScale;
            var xwDetail = this.opts.data.detailPeriodLen * xScale;

            //cache rendered version
            if (toCache) {
                var hash = [dims.w, dims.h, mini ? state.xg1 : state.x1, mini ? state.xg2 : state.x2, this.isDarkMode, zoom];
                if (!mini) {
                    hash.push(state.y1);
                    hash.push(state.y2);
                }
                for (var i = 0; i < ysLen; i++) {
                    hash.push(mini ? state['om_' + i] : state['o_' + i]);
                    hash.push(state['f_' + i]);
                }
                hash = hash.join(',');

                if (hash == this.cached) {
                    this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
                    return;
                }

                this.cached = hash;

                ctx.clearRect(0, 0, dims.w * dpi, dims.w * dpi);
            }

            // console.log(mini, this.opts.data.master ? 'master' : '', this.opts.data.slave ? 'slave' : '', 'render');

            var lineWidth = (opts.additional.mini ? 1 : (opts.data.strokeWidth == 'auto' ? (ysLen > 5 ? 1 : 2) : opts.data.strokeWidth)) * dpi;
            var lineWidthShift = lineWidth % 2 == 0 ? 0 : 0.5;
            ctx.lineWidth = lineWidth;
            ctx.lineCap = opts.additional.mini ? 'square' : 'round';
            ctx.lineJoin = opts.additional.mini ? 'square' : 'round';

            for (var i = 0; i < ysLen; i++) {
                o = mini ? state['om_' + i] : state['o_' + i];
                e = state['e_' + i];

                if (o > 0) {
                    y = ys[i].y;
                    yFrom = ys[i].yFrom;

                    if (opts.pairY) {
                        y1 = mini ? state['y1m_' + i] : state['y1_' + i];
                        y2 = mini ? state['y2m_' + i] : state['y2_' + i];
                    } else {
                        if (mini) {
                            if (e && o < 1) {
                                y1 = state['y1m_show'];
                                y2 = state['y2m_show'];
                            } else if (!e && o < 1) {
                                y1 = state['y1m_hidd'];
                                y2 = state['y2m_hidd'];
                            } else {
                                y1 = state['y1m'];
                                y2 = state['y2m'];
                            }
                        } else {
                            y1 = state['y1'];
                            y2 = state['y2'];
                        }
                    }

                    yScale = dpi * (dims.h - pTop - pBottom) / (y2 - y1);
                    yShift = (dims.h - pBottom + (toCache ? 0 : dims.t)) * dpi + y1 * yScale;

                    ctx.beginPath();
                    ctx.strokeStyle = this.isDarkMode ? ys[i].colors_n[0] : ys[i].colors_d[0];
                    ctx.globalAlpha = o * (state['f_' + i] * 0.9 + 0.1);


                    var yVal;
                    var xc;
                    var yc;
                    var minY = Number.MAX_VALUE;
                    var maxY = -Number.MAX_VALUE;
                    var prevXc = -Number.MAX_VALUE;
                    var prevYc;
                    var hasPrev = false;
                    var needMove = true;

                    for (var j = xInd1; j <= xInd2; j++) {
                        if (zoom) {
                            if (j >= d1 && j <= d2) {
                                yVal = yFrom[j] + morph * (y[j] - yFrom[j]);
                                xw = xwDetail;
                            } else {
                                yVal = y[j] + morph * (y[d1] - y[j]);//approx
                                xw = xwMain;
                            }
                        } else {
                            yVal = y[j];
                            xw = xwMain;
                        }

                        //skip absent values
                        if (isNaN(yVal)) {
                            needMove = true;
                            continue;
                        }

                        xc = x[j] * xScale + xShift << 0;
                        yc = yShift - yVal * yScale << 0;

                        if (xc > prevXc || (isStepMode && j == d2 + 1)) {
                            //merge vertical lines in one
                            if (hasPrev) {
                                if (prevYc == minY) {
                                    ctx.moveTo(prevXc + lineWidthShift, maxY - lineWidthShift);
                                    ctx.lineTo(prevXc + lineWidthShift, minY - lineWidthShift);
                                } else {
                                    ctx.moveTo(prevXc + lineWidthShift, minY - lineWidthShift);
                                    ctx.lineTo(prevXc + lineWidthShift, maxY - lineWidthShift);
                                    if (prevYc != maxY) {
                                        ctx.moveTo(prevXc + lineWidthShift, prevYc - lineWidthShift);
                                    }
                                }

                                hasPrev = false;
                            }

                            if (needMove) {
                                ctx.moveTo(xc + lineWidthShift, yc - lineWidthShift);
                                needMove = false;
                            }

                            minY = yc;
                            maxY = yc;
                            ctx.lineTo(xc + lineWidthShift, yc - lineWidthShift);
                            isStepMode && ctx.lineTo((x[j] * xScale + xw + xShift << 0) + lineWidthShift, yc - lineWidthShift);
                        } else {
                            minY = Math.min(minY, yc);
                            maxY = Math.max(maxY, yc);
                            hasPrev = true;
                        }
                        prevXc = xc;
                        prevYc = yc;
                    }

                    if (hasPrev) {
                        ctx.moveTo(prevXc + lineWidthShift, minY - lineWidthShift);
                        ctx.lineTo(prevXc + lineWidthShift, maxY - lineWidthShift);
                    }

                    ctx.stroke();
                }
            }

            ctx.globalAlpha = 1;

            toCache && this.opts.ctx.drawImage(this.$canvas, dims.l * dpi, dims.t * dpi);
        }
    }

    units.TLines = TLines;
})();
(function() {
    var units = window.Graph.units;

    var TSwitchers = function(opts) {
        this.opts = opts;

        var timeout;
        var preventClick;

        this.isTouch = units.TUtils.isTouchDevice();
        this.enabled = opts.data.ys.length;

        if (this.enabled == 1) {
            opts.$parent.style.display = 'none';
        }

        this.switchers = opts.data.ys.map(function(item, ind) {
            var $div = document.createElement('div');
            $div.className = 'tchart--switcher';

            if (opts.state['e_' + ind]) {
                $div.classList.toggle('tchart--switcher__active');
            } else {
                this.enabled--;
            }

            $div.setAttribute('data-label', item.label);
            opts.$parent.appendChild($div);

            var $span = document.createElement('span');
            $span.textContent = item.label;
            $div.appendChild($span);

            if (!this.isTouch) {
                $div.addEventListener('mouseenter', function(e) {
                    opts.state['e_' + ind] && opts.additional.onEnter(ind);
                });

                $div.addEventListener('mouseleave', function(e) {
                    opts.state['e_' + ind] && opts.additional.onLeave(ind);
                });
            }

            $div.addEventListener('click', function(e) {
                if (preventClick) {
                    preventClick = false;
                    return;
                }

                var isActive = $div.classList.contains('tchart--switcher__active');

                if (isActive && this.enabled == 1) {
                    $div.classList.add('tchart--switcher__denied');

                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        $div.classList.remove('tchart--switcher__denied');
                    }, 500);
                    return;
                }

                if (!this.isTouch) {
                    isActive ? opts.additional.onLeave(ind) : opts.additional.onEnter(ind);
                }

                opts.additional.onClick(!isActive, ind);
            }.bind(this));

            var dx, dy;
            var longTapTimer;

            this.drag = new units.TDrag({
                $el: $div,
                noPrevent: true,
                useElForMove: true,
                onDragStart: function(params) {
                    dx = params.pageX;
                    dy = params.pageY;

                    longTapTimer = setTimeout(function() {
                        preventClick = true;
                        if (!this.isTouch) {
                            opts.additional.onEnter(ind);
                        }
                        opts.additional.onLongTap(ind);
                    }.bind(this), 500);
                }.bind(this),
                onDragMove: function(params) {
                    if (Math.abs(dx - params.pageX) > 5 || Math.abs(dy - params.pageY) > 5) {
                        clearTimeout(longTapTimer);
                    }
                }.bind(this),
                onDragEnd: function(params) {
                    clearTimeout(longTapTimer);
                }.bind(this)
            });

            return $div;

        }.bind(this));

        this.updateColors();
    }

    TSwitchers.prototype = {
        onResize: function() {
        },

        updateColors: function() {
            var ys = this.opts.data.ys;
            for (var i = 0; i < this.switchers.length; i++) {
                this.switchers[i].style.color = this.isDarkMode ? ys[i].colors_n[1] : ys[i].colors_d[1];
            }
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
            this.updateColors();
        },

        render: function(o) {
            this.enabled = 0;

            for (var i = 0; i < this.switchers.length; i++) {
                if (o[i]) {
                    this.enabled++;
                    this.switchers[i].classList.add('tchart--switcher__active');
                } else {
                    this.switchers[i].classList.remove('tchart--switcher__active');
                }
            }
        }
    }

    units.TSwitchers = TSwitchers;
})();
(function() {
    var units = window.Graph.units;

    var TTip = function(opts) {
        this.opts = opts;

        this.shown = false;

        this.isTouch = units.TUtils.isTouchDevice();
        this.$canvas = opts.$canvas;
        this.cache = {};

        this.$tip = document.createElement('div');
        this.$tip.className = 'tchart--tip';
        opts.$parent.appendChild(this.$tip);

        this.$tipDt = document.createElement('h6');
        this.$tipDt.classList.add('unstyled');  // Sean: conflict with tocas
        this.$tip.appendChild(this.$tipDt);

        this.$tipDtText = document.createTextNode("");
        this.$tipDt.appendChild(this.$tipDtText);
        this.$tipDtText.nodeValue = '.';

        this.$tipArrow = document.createElement('div');
        this.$tipArrow.className = 'tchart--tip-arrow';
        this.$tip.appendChild(this.$tipArrow);

        this.$tipLoader = document.createElement('div');
        this.$tipLoader.className = 'tchart--tip-loader';
        this.$tip.appendChild(this.$tipLoader);

        this.$tipScrollerWrapper = document.createElement('div');
        this.$tipScrollerWrapper.className = 'tchart--tip-scroller-wrapper';
        this.$tip.appendChild(this.$tipScrollerWrapper);

        this.$tipScroller = document.createElement('div');
        this.$tipScroller.className = 'tchart--tip-scroller';
        this.$tipScrollerWrapper.appendChild(this.$tipScroller);

        this.updateTipScrollClasses = this.updateTipScrollClasses.bind(this);
        this.$tipScroller.addEventListener('scroll', this.updateTipScrollClasses);

        this.labels = [];
        opts.data.ys.forEach(function(item, ind) {
            this.labels.push(this.addLabel(item));
        }.bind(this));

        if (opts.graphStyle == 'bar' && opts.data.ys.length > 1) {
            this.allLabel = this.addLabel({
                label: 'All',
                outside: true
            });
        }

        if (opts.graphStyle == 'area') {
            this.pieLabel = this.addLabel({
                label: 'pie',
                outside: true
            });
        }

        this.tooltipOnHover = this.isTouch ? false : opts.data.tooltipOnHover;

        if (opts.graphStyle != 'bar') {
            this.$line = document.createElement('div');
            this.$line.className = 'tchart--line';
            opts.$parent.appendChild(this.$line);

            this.$lineFill = document.createElement('div');
            this.$line.appendChild(this.$lineFill);

            if (opts.graphStyle != 'area') {
                this.points = opts.data.ys.map(function(item, ind) {
                    var $el = document.createElement('span');
                    this.$line.appendChild($el);
                    return $el;
                }.bind(this));
            }
        } else {
            opts.state.barInd = -1;
            opts.state.barO = 0;
        }

        this.onMouseMove = this.onMouseMove.bind(this);
        this.onMouseLeave = this.onMouseLeave.bind(this);
        this.onBodyClick = this.onBodyClick.bind(this);
        this.onTipClick = this.onTipClick.bind(this);

        if (!this.tooltipOnHover) {
            this.drag = new units.TDrag({
                $el: this.$canvas,
                onDragStart: function(params) {
                    this.canvasPos = units.TUtils.getElemPagePos(this.$canvas);
                    this.dx = params.pageX - this.canvasPos.x;
                    this.dy = params.pageY - this.canvasPos.y;

                    var dims = this.opts.state.dims.tip;
                    this.tp = this.getTp(this.dx, this.dy - dims.t , params.isTouch);

                    delete this.prevXInd;

                    clearTimeout(this.showTimeout);
                    this.showTimeout = setTimeout(function() {
                        this.toggle(!!this.tp);
                        this.tp && this.render();
                    }.bind(this), this.isTouch ? 100 : 30);

                    document.body.removeEventListener('click', this.onBodyClick);

                    return !this.tp;
                }.bind(this),
                onDragMove: function(params) {
                    if (params.canceled) {
                        clearTimeout(this.showTimeout);
                        this.toggle(false);
                        return;
                    }
                    this.canvasPos = this.canvasPos || units.TUtils.getElemPagePos(this.$canvas);
                    this.dx = params.pageX - this.canvasPos.x;
                    this.dy = params.pageY - this.canvasPos.y;
                    this.render({isMove: true});
                }.bind(this),
                onDragEnd: function(params) {
                    delete this.canvasPos;
                    this.bodyTimeout = setTimeout(function() {
                        document.body.addEventListener('click', this.onBodyClick);
                    }.bind(this), 140);
                }.bind(this)
            });


            this.$tip.addEventListener('click', this.onTipClick);
        } else {
            this.$canvas.addEventListener('mousemove',  function(e) {
                this.canvasPos = this.canvasPos || units.TUtils.getElemPagePos(this.$canvas);
                this.dx = e.pageX - this.canvasPos.x;
                this.dy = e.pageY - this.canvasPos.y;

                var dims = this.opts.state.dims.tip;
                this.tp = this.getTp(this.dx, this.dy - dims.t, this.isTouch);

                if (this.tp) {
                    if (!this.shown) {
                        delete this.prevXInd;
                        this.toggle(true);
                        this.render({});
                    } else {
                        this.render({isMove: true});
                    }
                } else {
                    this.toggle(false);
                }
            }.bind(this));

            this.$canvas.addEventListener('mouseleave',  function() {
                delete this.canvasPos;
                this.toggle(false);
            }.bind(this));

            this.$canvas.addEventListener('click',  function(e) {
                if (this.shown) {
                    this.onTipClick(e);
                }
            }.bind(this));

            this.$tip.style.pointerEvents = 'none';
        }

        this.trackMouse(true);

        this.updateColors();
    }

    TTip.prototype = {
        onResize: function(rect) {
            var dims = this.opts.state.dims.tip;

            if (this.$line) {
                var dh = this.opts.graphStyle == 'area' ? 25 : 16;

                this.$line.style.top = (dims.t) + 'px';
                this.$line.style.height = (dims.h) + 'px';

                this.$lineFill.style.top = (dh) + 'px';
                this.$lineFill.style.bottom = (this.opts.settings.PADD[2] + 1) + 'px';
            }

            this.render();
        },


        abortDetailCallbacks: function() {
            if (!this.detailCallbacks) return;

            this.detailCallbacks.forEach(function(item, ind) {
                item.cancelled = true;
            });

            delete this.detailCallbacks;
        },


        onTipClick: function(e) {
            if (this.prevXInd == undefined) return;
            if (!this.opts.additional.onClick) return;
            if (!this.opts.data.hasDetail) return;
            if (this.opts.state.zoomMode || this.opts.state.zoomModeSpecial) return;

            e.stopPropagation();

            if (this.$tip.classList.contains('tchart--tip__loading')) return;

            var x = this.opts.data.x[this.prevXInd];

            //area case
            if (!this.opts.data.detailsFunc) {
                this.toggle(false, true);
                this.opts.additional.onClick(true, x);
                return;
            }

            this.$tip.classList.remove('tchart--tip__error');
            this.$tip.classList.add('tchart--tip__loading');

            if (this.cache[x]) {
                this.toggle(false, true);
                this.opts.additional.onClick(true, x, this.cache[x]);
                this.$tip.classList.remove('tchart--tip__loading');
                return;
            }

            this.abortDetailCallbacks();

            var dataPromise = this.opts.data.detailsFunc(x);
            var self = this;

            this.detailCallbacks = this.detailCallbacks || [];

            var successDetailCallback = function(val) {
                if (successDetailCallback.cancelled) return;
                self.$tip.classList.remove('tchart--tip__loading');

                if (!val || !val.columns) {
                    self.$tip.classList.add('tchart--tip__error');
                    return;
                }

                self.toggle(false, true);
                self.opts.additional.onClick(true, x, val);
                self.cache[x] = val;
            };

            var errorDetailCallback = function(val) {
                if (errorDetailCallback.cancelled) return;
                console.log('error:', val);
                self.$tip.classList.remove('tchart--tip__loading');
                self.$tip.classList.add('tchart--tip__error');
            };

            this.detailCallbacks.push(successDetailCallback);
            this.detailCallbacks.push(errorDetailCallback);

            dataPromise
                .then(successDetailCallback)
                .catch(errorDetailCallback);
        },


        updateColors: function() {
            var ys = this.opts.data.ys;

            this.labels.forEach(function(item, ind) {
                this.points && (this.points[ind].style.borderColor = this.isDarkMode ? ys[ind].colors_n[0] : ys[ind].colors_d[0]);
                item.$value.style.color = this.isDarkMode ? ys[ind].colors_n[2] : ys[ind].colors_d[2];
            }.bind(this));

            if (this.allLabel) {
                this.allLabel.$value.style.color = this.isDarkMode ? '#fff' : '#000';
            }

            if (this.$lineFill) {
                this.$lineFill.style.backgroundColor = this.isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(24, 45, 59, 0.1)';
            }
        },

        setDarkMode: function(enabled) {
            this.isDarkMode = enabled;
            this.updateColors();
        },

        addLabel: function(item) {
            var $row = document.createElement('div');
            $row.className = 'tchart--tip-row';
            if (item.outside) {
                this.$tip.appendChild($row);
                $row.classList.add('tchart--tip-row__outside');
            } else {
                this.$tipScroller.appendChild($row);
            }

            var $label = document.createElement('span');
            $row.appendChild($label);

            var $labelText = document.createTextNode("");
            $labelText.nodeValue = item.label;
            $label.appendChild($labelText);

            var $perText;

            if (this.opts.graphStyle == 'area') {
                var $per = document.createElement('p');
                $row.appendChild($per);

                $perText = document.createTextNode("");
                $per.appendChild($perText);
            }

            var $value = document.createElement('div');
            $row.appendChild($value);

            var $valueText = document.createTextNode("");
            $value.appendChild($valueText);

            return {
                $row: $row,
                $value: $value,
                $valueText: $valueText,
                $label: $label,
                $labelText: $labelText,
                $per: $per,
                $perText: $perText
            };
        },

        getTp: function(x, y, isTouch) {
            if (this.opts.graphStyle == 'area' && this.opts.state.zoomMode) {
                var dims = this.opts.state.dims.graph;
                var cx = (dims.w / 2);
                var cy = (dims.h / 2);
                var dist = Math.pow((cy - y) * (cy - y) + (x - cx) * (x - cx), 0.5);
                return dist <= this.opts.settings.PIE_RADIUS ? 'graph' : '';
            } else {
                var dims = this.opts.state.dims.tip;
                if (y < 0 || y > dims.h) return '';
                return 'graph';
            }
        },

        trackMouse: function(enabled, noPropagation) {
            if (this.isTouch) return;

            this.$canvas.addEventListener('mousemove', this.onMouseMove);
            this.$canvas.addEventListener('mouseleave', this.onMouseLeave);
        },

        onMouseLeave: function() {
            this.$canvas.classList.remove('tchart--graph-canvas__tip-pointer');
            delete this.canvasPos;
        },

        onMouseMove: function(e) {
            this.canvasPos = this.canvasPos || units.TUtils.getElemPagePos(this.$canvas);
            var dx = e.pageX - this.canvasPos.x;
            var dy = e.pageY - this.canvasPos.y;

            var dims = this.opts.state.dims.tip;
            var tp = this.getTp(dx, dy - dims.t, false);

            this.onMouseLeave();
            tp && this.$canvas.classList.add('tchart--graph-canvas__tip-pointer');
        },


        toggle: function(enabled, shiftHide) {
            var opts = this.opts;
            var state = opts.state;

            if (enabled && !this.shown) {
                this.$tip.classList.add('tchart--tip__visible');
                if (this.opts.data.hasDetail && !(this.opts.state.zoomMode || this.opts.state.zoomModeSpecial)) {
                    this.$tip.classList.add('tchart--tip__has-zoom');
                } else {
                    this.$tip.classList.remove('tchart--tip__has-zoom');
                }
                this.$tip.classList.remove('tchart--tip__shiftHide');
                this.$line && this.$line.classList.add('tchart--line__visible');
                units.TUtils.triggerEvent('chart-hide-tips', {except: this.opts.chart});
            }

            if (!enabled && this.shown) {
                delete this.lastCurPieItemInd;

                if (opts.graphStyle == 'area' && state.zoomMode) {
                    var animProps = [];
                    for (var i = 0; i < state.pieAngles.length; i++) {
                        var pieItem = state.pieAngles[i];
                        animProps.push({
                            prop: 'pieInd_' + pieItem.ind,
                            state: opts.state,
                            end: 0,
                            duration: 350,
                            tween: 'exp',
                            speed: 0.2,
                            group: {top: true}
                        });
                    }
                    opts.animator.add(animProps);
                }

                if (shiftHide) {
                    this.$tip.classList.add('tchart--tip__shiftHide');
                    this.lastTipTop -= 12;
                    this.lastTipLeft = this.lastTipLeft < this.opts.state.dims.tip.w / 2 ? this.lastTipLeft - 12 : this.lastTipLeft + 12;
                    this.$tip.style.transform = 'translate(' + this.lastTipLeft + 'px,' + this.lastTipTop + 'px)';
                    this.$tip.style.webkitTransform = 'translate(' + this.lastTipLeft + 'px,' + this.lastTipTop + 'px)';

                }
                this.$tip.classList.remove('tchart--tip__visible');
                this.$line && this.$line.classList.remove('tchart--line__visible');

                this.abortDetailCallbacks();

                this.$tip.classList.remove('tchart--tip__error');
                this.$tip.classList.remove('tchart--tip__loading');

                //add to animaion query request to disable tooltip selection overlay
                if (this.opts.graphStyle == 'bar') {
                    this.opts.animator.add([{
                        prop: 'barInd',
                        state: this.opts.state,
                        end: -1,
                        duration: 0,
                        delay: 150,
                        tween: 'linear',
                        group: {top: true}
                    }, {
                        prop: 'barO',
                        state: this.opts.state,
                        end: 0,
                        duration: 150,
                        tween: 'exp',
                        speed: 0.3,
                        group: {top: true}
                    }]);
                }
                document.body.removeEventListener('click', this.onBodyClick);
            }

            this.shown = enabled;
        },

        onBodyClick: function(e) {
            if (e.target == this.$canvas) return;

            this.toggle(false);
        },

        renderPieTooltip: function(params) {
            var opts = this.opts;
            var state = opts.state;
            var settings = this.opts.settings;
            var pTop = settings.PADD[0];
            var pRight = settings.PADD[1];
            var pBottom = settings.PADD[2];
            var pLeft = settings.PADD[3];
            var dims = state.dims.graph;
            var dimsTip = state.dims.tip;
            var cx = (dims.w / 2);
            var cy = (dims.h / 2);
            var formatter = units.TUtils.getFormatter('yTooltipFormatter', opts.data, state.zoomMorph);
            var ang = Math.atan2(cy - this.dy + dims.t, this.dx - cx);
            ang = ang < 0 ? Math.PI * 2 + ang : ang;

            var curPieItem;
            for (var i = 0; i < state.pieAngles.length; i++) {
                var pieItem = state.pieAngles[i];
                if (ang <= pieItem.st && ang >= pieItem.ed) {
                    curPieItem = pieItem;
                }

                if (ang - 2 * Math.PI <= pieItem.st && ang - 2 * Math.PI >= pieItem.ed) {
                    curPieItem = pieItem;
                }
            }

            if (this.lastCurPieItemInd != curPieItem.ind) {
                var animProps = [];
                for (var i = 0; i < state.pieAngles.length; i++) {
                    var pieItem = state.pieAngles[i];
                    animProps.push({
                        prop: 'pieInd_' + pieItem.ind,
                        state: opts.state,
                        end: pieItem == curPieItem ? 1 : 0,
                        duration: 350,
                        tween: 'exp',
                        speed: 0.2,
                        group: {top: true}
                    });
                }
                opts.animator.add(animProps);
            }


            this.pieLabel.$row.style.display = 'block';
            this.labels.forEach(function(item, ind) {
                item.$row.style.display = 'none';
            }.bind(this));

            this.pieLabel.$labelText.nodeValue = curPieItem.label;
            this.pieLabel.$valueText.nodeValue = !isNaN(curPieItem.value) ? formatter(curPieItem.value) : 'n/a';

            this.pieLabel.$value.style.color = curPieItem.color;
            this.$tip.classList.add('tchart--tip__piemode');
            this.$line && this.$line.classList.add('tchart--line__piemode');

            this.tipH = this.$tip.offsetHeight;
            this.tipW = this.$tip.offsetWidth;

            var tipMarginFromPointer = 20;
            var left = (this.dx - this.tipW / 2);
            var top = Math.min(this.dy - tipMarginFromPointer - this.tipH, dimsTip.t + dimsTip.h - this.tipH - pBottom);
            if (top < dimsTip.t + pTop) {
                top = dimsTip.t + pTop;
            }

            left = Math.min(Math.max(left, pLeft / 2), dimsTip.w - this.tipW - pRight / 2);

            this.$tip.style.transform = 'translate(' + (left << 0) + 'px,' + (top << 0) + 'px)';
            this.$tip.style.webkitTransform = 'translate(' + (left << 0) + 'px,' + (top << 0) + 'px)';

            this.lastCurPieItemInd = curPieItem.ind;

            this.updateTipScrollClasses();
        },

        render: function(params) {
            if (!this.shown) return;

//          var i = 0;
            var opts = this.opts;
            var state = opts.state;
            var settings = this.opts.settings;
            var pTop = settings.PADD[0];
            var pRight = settings.PADD[1];
            var pBottom = settings.PADD[2];
            var pLeft = settings.PADD[3];
            var $el;
            var y1, y2;
            var itemsVisible = 0;
            var dims = this.opts.state.dims.tip;
            var formatter = units.TUtils.getFormatter('yTooltipFormatter', opts.data, state.zoomMorph);

            var zoomMorph = (state.zoomMorph == undefined) ? 0 : state.zoomMorph;
            var offsetForBarGraphMain = opts.graphStyle == 'bar' || opts.graphStyle == 'step' ? this.opts.data.mainPeriodLen : 0;
            var offsetForBarGraphScale = offsetForBarGraphMain * (1 - zoomMorph);


            params = params || {};

            this.abortDetailCallbacks();

            this.$tip.classList.remove('tchart--tip__error');
            this.$tip.classList.remove('tchart--tip__loading');

            if (opts.graphStyle == 'area' && state.zoomMode) {
                this.renderPieTooltip(params);
                return;
            } else {
                if (this.pieLabel) {
                    this.pieLabel.$row.style.display = 'none';
                }
            }

            var constrainedDx = Math.max(Math.min(this.dx, dims.w - 1), 0);
            var x = state.x1 + (state.x2 - state.x1 + offsetForBarGraphScale) * ((constrainedDx - pLeft) / (dims.w - pRight - pLeft));
            var xPos = units.TUtils.getXIndex(opts.data.x, x);
            var xInd = (opts.graphStyle == 'bar' || opts.graphStyle == 'step' ? Math.floor(xPos) : Math.round(xPos));

            //prevent out of canvas points
            if (opts.graphStyle !== 'bar' && opts.graphStyle !== 'step') {
                var lx = (opts.data.x[xInd] - state.x1) / (state.x2 - state.x1 + offsetForBarGraphScale);
                var cx = ((lx * (dims.w - pLeft - pRight) + pLeft) << 0);
                if (cx < 0) {
                    xInd++;
                }
                if (cx > dims.w - 1) {
                    xInd--;
                }
            } else {
                if (this.opts.state.zoomMode || this.opts.state.zoomModeSpecial) {
                    if (xInd < opts.state.detailInd1) {
                        xInd++;
                    }
                    if (xInd > opts.state.detailInd2) {
                        xInd--;
                    }
                }
            }


            //add to anitaion query request to redraw graph
            //it will notice barInd: xInd and draw needed selection
            if (opts.graphStyle == 'bar') {
                opts.animator.add([{
                    prop: 'barInd',
                    state: opts.state,
                    end: xInd,
                    duration: 0,
                    tween: 'linear',
                    group: {top: true}
                }, {
                    prop: 'barO',
                    state: this.opts.state,
                    end: 1,
                    duration: 150,
                    tween: 'exp',
                    speed: 0.3,
                    group: {top: true}
                }]);
            }

            if (this.prevXInd != xInd || !params.isMove) {

                this.$tip.classList.remove('tchart--tip__piemode');
                this.$line && this.$line.classList.remove('tchart--line__piemode');


                this.labels.forEach(function(item, ind) {
                    var display = opts.state['e_' + ind] && !isNaN(opts.data.ys[ind].y[xInd]) ? 'block' : 'none';
                    item.$row.style.display = display;
                    this.points && (this.points[ind].style.display = display);
                    itemsVisible += display == 'block' ? 1 : 0;
                }.bind(this));

                this.itemsVisible = itemsVisible;
                if (this.allLabel) {
                    this.allLabel.$row.style.display = itemsVisible > 1 ? 'block' : 'none';
                }

                if (this.itemsVisible) {
                    this.$tip.classList.remove('tchart--tip__has_no_items');
                    this.$line && this.$line.classList.remove('tchart--line__has_no_items');
                } else {
                    this.$tip.classList.add('tchart--tip__has_no_items');
                    this.$line && this.$line.classList.add('tchart--line__has_no_items');
                }

                var xw = 0;
                if (opts.graphStyle == 'step') {
                    if (this.opts.state.zoomMode) {
                        xw = opts.data.detailPeriodLen;
                    } else {
                        xw = opts.data.mainPeriodLen;
                    }
                }

                var lx = (opts.data.x[xInd] - state.x1 + xw / 2) / (state.x2 - state.x1 + offsetForBarGraphScale);
                var cx = ((lx * (dims.w - pLeft - pRight) + pLeft) << 0);

                this.$line && (this.$line.style.transform = 'translateX(' + cx + 'px)');
                this.$line && (this.$line.style.webkitTransform = 'translateX(' + cx + 'px)');

                this.$tipDtText.nodeValue = opts.data.dates[xInd];

                var sumAll = 0;
                opts.data.ys.forEach(function(item, ind) {
                    if (opts.state['e_' + ind] && !isNaN(item.y[xInd])) {
                        this.labels[ind].$valueText.nodeValue = formatter(item.y[xInd]);
                        sumAll += item.y[xInd] || 0;

                        if (this.points) {
                            $el = this.points[ind];

                            if (opts.pairY) {
                                y1 = state['y1_' + ind];
                                y2 = state['y2_' + ind];
                            } else {
                                y1 = state['y1'];
                                y2 = state['y2'];
                            }

                            var y = (item.y[xInd] - y1) / (y2 - y1);
                            $el.style.transform = 'translateY(' + ((dims.h - y * (dims.h - pTop - pBottom) - pBottom) << 0) + 'px)';
                            $el.style.webkitTransform = 'translateY(' + ((dims.h - y * (dims.h - pTop - pBottom) - pBottom) << 0) + 'px)';
                        }
                    }
                }.bind(this));



                if (this.allLabel) {
                    this.allLabel.$valueText.nodeValue = formatter(sumAll);
                }

                if (!params.isMove) {
                    //reset max width for current tooltip shown session
                    this.maxLabelWidth = 0;
                    this.maxValueWidth = 0;
                    this.maxPercentageWidth = 0;
                    this.maxDateWidth = 0;

                    //calc content paddings
                    var compStyles = window.getComputedStyle(this.$tip);
                    this.rowPaddings = parseInt(compStyles.getPropertyValue('padding-left'), 10) + parseInt(compStyles.getPropertyValue('padding-right'), 10);
                }


                if (opts.graphStyle == 'area') {
                    this.fillPercentages(xInd, sumAll);
                }

                //calc max labels and values width
                this.labels.forEach(function(item, ind) {
                    var isVisible = opts.state['e_' + ind] && !isNaN(opts.data.ys[ind].y[xInd]);
                    if (isVisible) {
                        var labelWidth = item.$label.offsetWidth;
                        if (labelWidth > this.maxLabelWidth) {
                            this.maxLabelWidth = labelWidth;
                        }
                        var valueWidth = item.$value.offsetWidth;
                        if (valueWidth > this.maxValueWidth) {
                            this.maxValueWidth = valueWidth;
                        }
                    }
                }.bind(this));


                //calc tooltip width to fit all labels and values witout overlaping each another
                var minWidth = this.rowPaddings + this.maxLabelWidth + 20 +this.maxValueWidth;//20 - min margin between label and value
                minWidth += opts.graphStyle == 'area' ? this.maxPercentageWidth : 0; //percentages block for area

                //and don't forget about date caption, 20 - space for detail arrow
                var dateWidth = this.rowPaddings + this.$tipDt.offsetWidth + 20;
                if (dateWidth > this.maxDateWidth) {
                    this.maxDateWidth = dateWidth;
                }
                minWidth = Math.max(minWidth, this.maxDateWidth);

                this.$tip.style.width = minWidth + 'px';

                this.tipH = this.$tip.offsetHeight;
                this.tipW = this.$tip.offsetWidth;
            }

            var pos = this.itemsVisible <= 2 ? 'center' : 'side';
            var tipMarginFromPointer = 20;

            if (pos == 'center') {
                var left = (this.dx - this.tipW / 2);
                var top = Math.min(this.dy - tipMarginFromPointer - this.tipH, dims.t + dims.h - this.tipH - pBottom);
                if (top < dims.t + pTop) {
                    pos = 'side';
                }
            }

            if (pos == 'side') {
                if (this.dx > dims.w / 2) {
                    var left = this.dx - this.tipW - tipMarginFromPointer;
                } else {
                    var left = this.dx + tipMarginFromPointer;
                }
                var top = Math.min(Math.max(this.dy - this.tipH / 2, dims.t + pTop), dims.t + dims.h - this.tipH - pBottom);
            }

            left = Math.min(Math.max(left, pLeft / 2), dims.w - this.tipW - pRight / 2);

            this.$tip.style.transform = 'translate(' + (left << 0) + 'px,' + (top << 0) + 'px)';
            this.$tip.style.webkitTransform = 'translate(' + (left << 0) + 'px,' + (top << 0) + 'px)';

            this.lastTipLeft = left << 0;
            this.lastTipTop = top << 0;

            this.prevXInd = xInd;

            this.updateTipScrollClasses();
        },


        updateTipScrollClasses: function() {
            if (this.$tipScroller.scrollHeight > this.$tipScroller.offsetHeight) {
                this.$tip.classList.add('tchart--tip__scroll');
            } else {
                this.$tip.classList.remove('tchart--tip__scroll');
            }

            if (this.$tipScroller.scrollTop <= 0) {
                this.$tip.classList.remove('tchart--tip__has_less');
            } else {
                this.$tip.classList.add('tchart--tip__has_less');
            }

            if (this.$tipScroller.scrollTop >= this.$tipScroller.scrollHeight - this.$tipScroller.offsetHeight) {
                this.$tip.classList.remove('tchart--tip__has_more');
            } else {
                this.$tip.classList.add('tchart--tip__has_more');
            }
        },


        fillPercentages: function(xInd, sumAll) {
            var opts = this.opts;
            var perInt = [];
            var maxLen = 2;

            opts.data.ys.forEach(function(item, ind) {
                if (opts.state['e_' + ind]) {
                    perInt[ind] = Math.max(Math.round(100 * item.y[xInd] / sumAll), 1);

                    if (isNaN(item.y[xInd])) {
                        perInt[ind] = 0;
                    }

                    if (perInt[ind] == 100) {
                        maxLen = 3;
                    }
                }
            }.bind(this));

            opts.data.ys.forEach(function(item, ind) {
                if (opts.state['e_' + ind]) {
                    var percentageWidth = (maxLen * 8 + 17); //with right padding
                    this.labels[ind].$label.style.transform = 'translateX(' + percentageWidth + 'px)';
                    this.labels[ind].$label.style.webkitTransform = 'translateX(' + percentageWidth + 'px)';
                    this.labels[ind].$perText.nodeValue = (perInt[ind]) + '%';
                    this.labels[ind].$per.style.width = (percentageWidth - 7) + 'px';
                    if (percentageWidth > this.maxPercentageWidth) {
                        this.maxPercentageWidth = percentageWidth;
                    }
                }
            }.bind(this));
        }
    }

    units.TTip = TTip;
})();
(function() {
    Math.log2 = Math.log2 || function(x) {
        return Math.log(x) * Math.LOG2E;
    };

    Math.log10 = Math.log10 || function(x) {
        return Math.log(x) * Math.LOG10E;
    };

//  var cache = {};
//  var timeout;

    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var monthsFull = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];


    var TUtils = {
        simplifyData: function(tp, x, ys, xScale, xShift, visibleCols, xInd1, xInd2, dw) {
            var pointsPerPixel = (xInd2 - xInd1) / dw;
            var optX = [];
            var optYs = [];

            if (pointsPerPixel <= 1) {
                return {
                    xInd1: xInd1,
                    xInd2: xInd2,
                    x: x,
                    ys: ys
                };
            } else {
                var xInd = 0;
                var xPrev = -999999999;
                var colsLen = visibleCols.length;
                var cnt;

                for (var i = xInd1; i <= xInd2; i++) {
                    var tmpX = x[i] * xScale + xShift << 0;
                    var notTheSame = tmpX > xPrev;

                    if (notTheSame) {
                        optX[xInd] = x[i];
                        xInd++;
                    } else {
                        cnt++;
                    }

                    //calc avg y per column that fits inside same x in pixels
                    for (var j = 0; j < colsLen; j++) {
                        var visColInd = visibleCols[j];
                        optYs[visColInd] = optYs[visColInd] || {y: []};
                        var prevOptY = optYs[visColInd].y[xInd - 1];
                        var curY = ys[visColInd].y[i];
                        if (prevOptY == undefined) {
                            optYs[visColInd].y[xInd - 1] = curY;
                        } else {
                            optYs[visColInd].y[xInd - 1] += curY;
                        }
                        if (xInd > 1) {
                            if (notTheSame) {
                                optYs[visColInd].y[xInd - 2] /= cnt;
                            }
                            if (i == xInd2) {
                                optYs[visColInd].y[xInd - 1] /= cnt;
                            }
                        }
                    }

                    if (notTheSame) {
                        cnt = 1;
                    }

                    xPrev = tmpX;
                }

//              xInd1 = 0;
//              xInd2 = xInd - 1;

                return {
                    isOptimized: pointsPerPixel > 1,
                    xInd1: 0,
                    xInd2: xInd - 1,
                    x: optX,
                    ys: optYs
                };
            }
        },

        getElemPagePos: function($el) {
            var rect = $el.getBoundingClientRect();

            return {
                x: rect.left + (window.pageXOffset || document.documentElement.scrollLeft),
                y: rect.top + (window.pageYOffset || document.documentElement.scrollTop)
            };
        },

        getXIndex: function(x, xc, doNotClip) {
            var i1 = 0;
            var i2 = x.length - 1;

            if (!doNotClip) {
                if (xc < x[i1]) {
                    xc = x[i1];
                } else if (xc > x[i2]) {
                    xc = x[i2];
                }
            }

            while (Math.abs(i1 - i2) > 1) {
                var i = Math.round((i1 + i2) / 2);

                if (xc >= x[i1] && xc <= x[i]) {
                    i2 = i;
                } else {
                    i1 = i;
                }
            }

            return i1 + (xc - x[i1]) / (x[i2] - x[i1]);
        },


        triggerEvent: function(eventName, details) {
            if (typeof window.CustomEvent != 'function') return;
            document.dispatchEvent(new CustomEvent(eventName, {detail: details || null}));
        },


        isTouchDevice: function() {
            var prefixes = ' -webkit- -moz- -o- -ms- '.split(' ');
            var mq = function(query) {
                return window.matchMedia(query).matches;
            }

            if (('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch) {
                return true;
            }

            var query = ['(', prefixes.join('touch-enabled),('), 'heartz', ')'].join('');
            return mq(query);
        },


        drawRoundedRect: function(ctx, dpi, w, h, x, y, r) {
            w *= dpi;
            h *= dpi;
            x *= dpi;
            y *= dpi;

            if (typeof r == 'number') {
                r = [r, r, r, r];
            }

            r[0] *= dpi; //tl
            r[1] *= dpi; //tr
            r[2] *= dpi; //br
            r[3] *= dpi; //bl

            ctx.beginPath();
            ctx.moveTo(x + r[0], y);
            ctx.lineTo(x + w - r[1], y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r[1]);
            ctx.lineTo(x + w, y + h - r[2]);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r[2], y + h);
            ctx.lineTo(x + r[3], y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r[3]);
            ctx.lineTo(x, y + r[0]);
            ctx.quadraticCurveTo(x, y, x + r[0], y);
            ctx.closePath();
        },


        drawRoundedRect2: function(ctx, dpi, w, h, x, y, r) {
            w *= dpi;
            h *= dpi;
            x *= dpi;
            y *= dpi;
            r *= dpi;

            if (w < 2 * r) r = w / 2;
            if (h < 2 * r) r = h / 2;
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        },


        getFormatter: function(formatterName, data, zoomMorph) {
            var func;

            if (zoomMorph > 0.5 && data.details) {
                func = data.details[formatterName];
            }

            return func || data[formatterName] || TUtils[formatterName];
        },


        xTooltipFormatter: function(x, isZoom) {
            var dt = new Date(x);
            var dtc = [dt.getFullYear(), dt.getMonth(), dt.getDate(), dt.getDay(), dt.getHours(), dt.getMinutes()];  // Sean: Use local time instead of UTC

            if (isZoom) {
                return (dtc[4] < 10 ? '0' : '') + dtc[4] + ':' + (dtc[5] < 10 ? '0' : '') + dtc[5];
            } else {
                return days[dtc[3]] + ', ' + dtc[2] + ' ' + months[dtc[1]] + ' ' + dtc[0];
            }
        },


        xTickFormatter: function(x, isZoom) {
            var dt = new Date(x);
            var dtc = [dt.getFullYear(), dt.getMonth(), dt.getDate(), dt.getDay(), dt.getHours(), dt.getMinutes()];  // Sean: Use local time instead of UTC

            if (isZoom) {
                return (dtc[4] < 10 ? '0' : '') + dtc[4] + ':' + (dtc[5] < 10 ? '0' : '') + dtc[5];
            } else {
                return dtc[2] + ' ' + months[dtc[1]];
            }
        },


        xRangeFormatter: function(x, isZoom) {
            var dt = new Date(x);
            var dtc = [dt.getFullYear(), dt.getMonth(), dt.getDate()];  // Sean: Use local time instead of UTC

            return dtc[2] + ' ' + monthsFull[dtc[1]] + ' ' + dtc[0];
        },


        yTickFormatter: function(val, step, isFractional) {
            if (val == 0) return 0;

            if (step < 1000) {
                return Math.floor(val);
            } else {
                if (step >= 1000 && step < 1000000) {
                    if (isFractional) {
                        return Math.floor(10 * val / 1000) / 10 + 'K';
                    } else {
                        return Math.round(val / 1000) + 'K';
                    }
                } else {
                    if (isFractional) {
                        return Math.floor(10 * val / 1000000) / 10 + 'M';
                    } else {
                        return Math.round(val / 1000000) + 'M';
                    }
                }
            }
        },


        yTooltipFormatter: function(val) {
            if (typeof val !== 'number') {
                return typeof val === 'string' ? val : '?';
            }
            // var endingZeroesReg = new RegExp('\\.0+$', 'g');
            // var numValue = item.y[xInd];
            // if (typeof numValue === 'number') {
            //     numValue = numValue.toFixed(2).toString(10);
            // } else {
            //     numValue = numValue + '';
            // }
            // this.labels[ind].$valueText.nodeValue = numValue.replace(endingZeroesReg, '').replace(thSepReg, ' ');
            return TUtils.statsFormatKMBT(val);
        },


        statsFormatKMBT: function(val, kmbt, precision) {
            if (val == 0) {
                return '0';
            }
            if (kmbt == null) {
                kmbt = TUtils.statsChooseNumKMBT(val)
            }
            var sval = TUtils.statsFormatFixedKMBT(val, kmbt);
            if (precision == null) {
                precision = TUtils.statsChoosePrecision(sval)
            }
            return sval.toFixed(precision) + kmbt;
        },


        statsFormatFixedKMBT: function(val, kmbt) {
            switch (kmbt) {
                case 'K':
                    return val / 1000;
                case 'M':
                    return val / 1000000;
                case 'B':
                    return val / 1000000000;
                case 'T':
                    return val / 1000000000000;
            }
            return val
        },


        statsChoosePrecision: function(val) {
            var absVal = Math.abs(val);
            if (absVal > 10) {
                return 0;
            }
            if (absVal >= 1.0) {
                return (Math.abs(absVal - Math.floor(absVal)) < 0.001) ? 0 : 1;
            }
            return 2;
        },


        statsChooseNumKMBT: function(val) {
            var absVal = Math.abs(val);
            if (absVal >= 1000000000000) {
                return 'T';
            } else if (absVal >= 1000000000) {
                return 'B';
            } else if (absVal >= 1000000) {
                return 'M';
            } else if (absVal >= 2000) {
                return 'K';
            }
            return '';
        },


        roundRange: function(y1, y2, cnt, refRange) {
            //for paired graphs, second one should fit range of the first one (wich is rounded)
            if (Math.abs(y2 - y1) < 1) {
                y1 -= y1 / 10;
                y2 += y2 / 10;
            }
            if (refRange) {
                var yd1 = (refRange.yMinOrig - refRange.yMin) / (refRange.yMaxOrig - refRange.yMinOrig);
                var yd2 = (refRange.yMax - refRange.yMaxOrig) / (refRange.yMaxOrig - refRange.yMinOrig);
                return {
                    yMin: y1 - (y2 - y1) * yd1,
                    yMax: y2 + (y2 - y1) * yd2,
                    yMinOrig: y1,
                    yMaxOrig: y2,
                };
            }

            var calc = function(d) {
                var power = curPower * d;
                var min = Math.floor(y1 / power) * power;
                var max = min + cnt * Math.ceil((y2 - min) * scale / power) * power;

                return {
                    good: max <= maxLevel && min >= minLevel,
                    yMin: Math.round(min),
                    yMax: Math.round(max),
                    yMinOrig: y1,
                    yMaxOrig: y2,
                };
            };

            var scale = 1 / cnt;
            var step = (y2 - y1) * scale;
            var curPower = Math.max(Math.pow(10, Math.floor(Math.log10(step))), 1);
            var minLevel = y1 - step * 0.5;
            var maxLevel = y2 + step * 0.5;
            var range;

            var c = 1;

            while (true) {
                range = calc(5);
                if (range.good) break;
                range = calc(2);
                if (range.good) break;
                range = calc(1);
                if (range.good) break;
                curPower *= 0.1;
                c++;
                if (c > 10) { //seems impossible? but have no time to prove it, so this is save exit from cycle
                    return {
                        yMinOrig: y1,
                        yMaxOrig: y2,
                        yMin: y1,
                        yMax: y2
                    };
                }
            }

            return range;
        }
    };

    window.Graph.units.TUtils = TUtils;
})();
