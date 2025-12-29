/**
 * Data for jqplot is a two-dimensional array in the form of [date, value]
 */
const VitalStats = {
  systolicTrouble: false,
  diastolicTrouble: false,
  systolicTroubleType: 0,
  plots: [],
  debugEnabled: false,

  init: function () {
    this.extendJQPlot();
  },

  initEvents: function () {

  },

  extendJQPlot: function () {
    $.jqplot.tickNumberFormatter1 = function (format, val) {
      if (typeof val == 'number') {
        if (val == 150)
          return String("Hypertension");
        else if (val == 130)
          return String("Prehypertension");
        else if (val == 110)
          return String("Good");
        else
          return String(" ");
      }
      else
        return String(val);
    }
  },

  evalBP: function (lastThree, max) {
    if (lastThree.length < 3) {
      return false;
    }

    let aboveMax = false;

    if (lastThree[0][1] > max && lastThree[1][1] > max && lastThree[2][1] > max) {
      aboveMax = true;
    }

    const beginDate = new Date(lastThree[0][0]);
    const endDate = new Date(lastThree[2][0]);
    const elapsedDays = parseInt((endDate - beginDate) / (24 * 3600 * 1000));

    if (elapsedDays > 90) {	// more than 90 days and this is moot
      aboveMax = false;
    }

    return aboveMax;
  },

  isOver170: function (item) {
    const beginDate = new Date(item[0]);
    const endDate = new Date();
    const elapsedDays = parseInt((endDate - beginDate) / (24 * 3600 * 1000));

    return (elapsedDays <= 90 && item[1] > 170);
  },

  doBPChart: function (id, data, title) {
    // Get last three
    const lastThree = [];
    let length, min, max;
    let ygood, ygoodwidth;
    let yprehyper, yprehyperwidth;
    let yhyper, yhyperwidth;

    if (data.length > 3) {
      length = data.length;
      lastThree = [data[length - 3], data[length - 2], data[length - 1]];
    }

    if (title.toLowerCase() == 'systolic') {
      min = 100;
      max = 180;
      ygood = 0; ygoodwidth = 2;
      yprehyper = 120; yprehyperwidth = 2;
      yhyper = 140; yhyperwidth = 2;

      this.systolicTrouble = false;

      for (let ix = data.length - 1; ix >= 0; ix--) {
        if (this.isOver170(data[ix])) {
          this.systolicTrouble = true;
          this.systolicTroubleType = 170;
          break;
        }
      }

      if (!this.systolicTrouble) {
        this.systolicTrouble = this.evalBP(lastThree, yhyper);
        this.systolicTroubleType = yhyper;
      }
    }
    else if (title.toLowerCase() == 'diastolic') {
      min = 60;
      max = 120;
      ygood = 0; ygoodwidth = 2;
      yprehyper = 80; yprehyperwidth = 2;
      yhyper = 90; yhyperwidth = 2;
      this.diastolicTrouble = this.evalBP(lastThree, yhyper);
    }

    $.jqplot.postDrawHooks.push(function () {
      $(".jqplot-overlayCanvas-canvas").css('z-index', '0');	//send overlay canvas to back
      $(".jqplot-series-canvas").css('z-index', '1');			//send series canvas to front
      $(".jqplot-highlighter-tooltip").css('z-index', '2');
      $(".jqplot-event-canvas").css('z-index', '5');			//must be on the very top since it is responsible for event catching and propagation
    });

    const plot = $.jqplot(id, [data], {
      title: title,
      series: [{ showMarker: true }],
      seriesDefaults: {
        showMarker: false,
        pointLabels: { show: true }
      },
      highlighter: {
        sizeAdjust: 7.5,
        show: true,
        tooltipLocation: 'n',
        useAxesFormatters: true,
      },
      tickOptions: {
        formatString: '%d'
      },
      canvasOverlay: {
        show: true,
        objects: [
          {
            horizontalLine: {
              name: '/Good',
              y: ygood,
              lineWidth: ygoodwidth,
              color: 'rgba(0,255,0,0.75)',
              shadow: false
            }
          },
          /*
          {
            horizontalLine: {
              name: 'Prehypertension',
              y: yprehyper,
              lineWidth: yprehyperwidth,
              color: 'rgba(255,255,0,0.75)',
              shadow: false;
            }
          },
          */
          {
            horizontalLine: {
              name: 'Hypertension',
              y: yhyper,
              lineWidth: yhyperwidth,
              color: 'rgba(255,0,0,0.95)',
              shadow: false
            }
          }
        ]
      },
      axes: {
        xaxis:
        {
          renderer: $.jqplot.DateAxisRenderer,
          tickOptions: { formatString: '%b, %Y' }
        },
        yaxis:
        {
          min: min,
          max: max,
          tickOptions: {
            formatter: $.jqplot.tickNumberFormatter // my formatter
          }
        }
      }
    });

    this.plots.push(plot);
  },

  doGlucoseChart: function (id, data, title) {
    min = 50;
    max = 150;
    ylow = 100; ylowwidth = 2;
    yhigh = 125; yhighwidth = 2;

    let newmax = max;
    let newmin = min;

    for (var ix = 0; ix < data.length; ix++) {
      if (data[ix][1] > max) {
        newmax = data[ix][1];
      }

      if (data[ix][1] < min) {
        newmin = data[ix][1];
      }
    }

    if (newmax > max) {
      max = newmax + 30;
    }

    if (newmin < min) {
      min = newmin - 30;
    }

    if (min < 0) {
      min = 0;
    }

    $.jqplot.postDrawHooks.push(function () {
      $(".jqplot-overlayCanvas-canvas").css('z-index', '/0');	//send overlay canvas to back
      $(".jqplot-series-canvas").css('z-index', '1');			//send series canvas to front
      $(".jqplot-highlighter-tooltip").css('z-index', '2');
      $(".jqplot-event-canvas").css('z-index', '5');			//must be on the very top since it is responsible for event catching and propagation
    });

    const plot = $.jqplot(id, [data], {
      title: title,
      series: [{ showMarker: true }],
      seriesDefaults: {
        showMarker: false,
        pointLabels: { show: true }
      },
      highlighter: {
        sizeAdjust: 7.5,
        show: true,
        tooltipLocation: 'n',
        useAxesFormatters: true,
      },
      tickOptions: {
        formatString: '%d'
      },
      canvasOverlay: {
        show: true,
        objects: [
          {
            horizontalLine: {
              name: 'Low',
              y: ylow,
              lineWidth: ylowwidth,
              color: 'rgba(255,255,0,0.75)',
              shadow: false
            }
          },
          {
            horizontalLine: {
              name: 'High',
              y: yhigh,
              lineWidth: yhighwidth,
              color: 'rgba(255,0,0,0.95)',
              shadow: false
            }
          }
        ]
      },
      axes: {
        xaxis:
        {
          renderer: $.jqplot.DateAxisRenderer,
          tickOptions: { formatString: '%b, %Y' }
        },
        yaxis:
        {
          min: min,
          max: max,
          tickOptions: {
            formatter: $.jqplot.tickNumberFormatter // my formatter
          }
        }
      }
    });

    this.plots.push(plot);
  },

  doChart: function (id, data, title) {
    const plot = $.jqplot(id, [data], {
      title: title,
      axes: {
        xaxis: {
          renderer: $.jqplot.DateAxisRenderer,
          tickOptions: { formatString: '%b, %Y' }
        }
      },
      highlighter: {
        show: true,
        sizeAdjust: 7.5
      },
      cursor: {
        show: false
      }
    });

    this.plots.push(plot);
  },

  debug: function(obj) {
    if (this.debugEnabled) {
      console.log(obj);
    }
  }
}

VitalStats.init();