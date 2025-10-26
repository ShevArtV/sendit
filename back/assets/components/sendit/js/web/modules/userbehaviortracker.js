import {Base} from './base.js';

export class UserBehaviorTracker extends Base {
  initialize() {
    this.metrics = {
      mouseMovements: [],
      clicks: [],
      keystrokes: [],
      scrolls: [],
      touches: [],
      focusEvents: [],
      startTime: Date.now(),
      interactions: 0,
      lastUpdateTime: Date.now(),
      behaviorHistory: []
    };

    this.scoreDetails = {
      analyzeMouseMovements: 0, analyzeClicks: 0, analyzeKeystrokes: 0, analyzeInteractionTiming: 0, totalScore: 0
    };
    this.suspicionScore = 0;
    this.autoUpdateInterval = null;

    this.detectAutomation();
    this.bindEvents();
    this.startAutoUpdate();

  }

  detectAutomation() {
    let score = 0;

    if (navigator.webdriver) score += 30;

    if (navigator.userAgentData && navigator.userAgentData.brands.some(b => b.brand === 'HeadlessChrome')) {
      score += 20;
    }

    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 2) score += 10;

    if (!navigator.languages || navigator.languages.length === 0) score += 10;

    const testKey = '__test__';
    try {
      localStorage.setItem(testKey, testKey);
      localStorage.removeItem(testKey);
    } catch {
      score += 5;
    }

    if (window.outerWidth === 0 && window.outerHeight === 0) score += 15;

    if (screen.width < 100 || screen.height < 100) score += 15;

    const permissions = navigator.permissions;
    if (!permissions || typeof permissions.query !== 'function') {
      score += 5;
    }

    this.suspicionScore += score;
  }

  bindEvents() {
    const eventOptions = {passive: true};

    document.addEventListener('mousemove', this.trackMouseMove.bind(this), eventOptions);
    document.addEventListener('click', this.trackClick.bind(this), eventOptions);
    document.addEventListener('keydown', this.trackKeystroke.bind(this), eventOptions);
    document.addEventListener('scroll', this.trackScroll.bind(this), eventOptions);
    document.addEventListener('touchstart', this.trackTouch.bind(this), eventOptions);
    document.addEventListener('touchmove', this.trackTouch.bind(this), eventOptions);
    document.addEventListener('focus', this.trackFocus.bind(this), true);
    document.addEventListener('blur', this.trackFocus.bind(this), true);
  }

  addMetricWithLimit(metricArray, eventData) {
    const now = Date.now();
    metricArray.push(eventData);
    if (metricArray.length > this.config.maxEvents) {
      metricArray.shift();
    }

    const diff = now - this.metrics.lastUpdateTime;
    if (diff <= this.config.behavior.minTimeThreshold) {
      this.metrics.interactions++;
    }
  }

  trackMouseMove(e) {
    this.addMetricWithLimit(this.metrics.mouseMovements, {
      x: e.clientX, y: e.clientY, time: Date.now()
    });
  }

  trackClick(e) {
    this.addMetricWithLimit(this.metrics.clicks, {
      x: e.clientX, y: e.clientY, time: Date.now(), button: e.button
    });
  }

  trackKeystroke(e) {
    this.addMetricWithLimit(this.metrics.keystrokes, {
      key: e.key, time: Date.now(), duration: 0
    });
  }

  trackScroll() {
    this.addMetricWithLimit(this.metrics.scrolls, {
      x: window.scrollX, y: window.scrollY, time: Date.now()
    });
  }

  trackTouch(e) {
    if (e.touches.length > 0) {
      this.addMetricWithLimit(this.metrics.touches, {
        x: e.touches[0].clientX, y: e.touches[0].clientY, time: Date.now(), type: e.type
      });
    }
  }

  trackFocus(e) {
    const focusEvent = {
      type: e.type, time: Date.now()
    };

    this.metrics.focusEvents.push(focusEvent);
    if (this.metrics.focusEvents.length > this.config.maxEvents) {
      this.metrics.focusEvents.shift();
    }
  }

  startAutoUpdate() {
    if (!this.config.autoUpdateTime) return;

    this.autoUpdateInterval = setInterval(() => {
      this.requestAnalysis();
    }, this.config.autoUpdateTime);
  }

  stopAutoUpdate() {
    if (this.autoUpdateInterval) {
      clearInterval(this.autoUpdateInterval);
      this.autoUpdateInterval = null;
    }
  }

  requestAnalysis() {
    this.analyzePatterns();
    const result = {
      score: Math.round(this.suspicionScore),
      details: {...this.scoreDetails},
      isBot: Math.round(this.suspicionScore) > this.config.minBotScoreAmount,
      timestamp: Date.now()
    };
    this.updateCookie(result);
    this.metrics.interactions = 0;
    return result;
  }

  analyzePatterns() {
    const now = Date.now();
    const timeSinceLastUpdate = now - (this.metrics.lastUpdateTime || now);
    this.metrics.lastUpdateTime = now;

    this.scoreDetails = {
      mouseScore: this.analyzeMouseMovements() * this.config.weights.mouseMovements,
      clickScore: this.analyzeClicks() * this.config.weights.clicks,
      keystrokeScore: this.analyzeKeystrokes() * this.config.weights.keystrokes,
      scrollScore: this.analyzeScrolls() * this.config.weights.scrolls,
      timingScore: this.analyzeInteractionTiming() * this.config.weights.timing,
      patternScore: this.analyzeBehaviorPatterns() * this.config.weights.behaviorPatterns,
    };

    const values = Object.values(this.scoreDetails);
    const sum = values.reduce((total, score) => total + score, 0);
    let totalScore = Math.round(sum / values.length);
    const cooldown = Math.min(100, Math.max(0, totalScore * this.config.cooldownMultiplier * (timeSinceLastUpdate / 1000)));
    if (this.metrics.behaviorHistory.length > this.config.behavior.historySize) {
      this.metrics.behaviorHistory.shift();
    }

    const recentBehavior = this.calculateRecentBehaviorScore();
    this.suspicionScore = Math.min(100, Math.max(0, totalScore + recentBehavior * this.config.weights.recentBehavior - cooldown));
    this.metrics.behaviorHistory.push({
      time: now, score: this.suspicionScore
    });

    if (this.config.debug) {
      console.log(recentBehavior * this.config.weights.recentBehavior, cooldown, this.suspicionScore, totalScore, this.scoreDetails);
    }
  }

  calculateRecentBehaviorScore() {
    const recentWindow = this.metrics.behaviorHistory.slice(-this.config.behavior.recentWindow);
    return recentWindow.reduce((sum, item, idx, arr) => sum + (item.score * (idx + 1) / arr.length), 0);
  }

  analyzeMouseMovements() {
    if (this.metrics.mouseMovements.length < this.config.mouse.minMovements) return 0;

    const checkup = {
      straightLine: 0,
      rightAngle: 0,
    };

    const movements = this.metrics.mouseMovements;
    let straightLineCount = 0;
    let rightAngleCount = 0;

    for (let i = 2; i < movements.length; i++) {
      const dx1 = movements[i - 1].x - movements[i - 2].x;
      const dy1 = movements[i - 1].y - movements[i - 2].y;
      const dx2 = movements[i].x - movements[i - 1].x;
      const dy2 = movements[i].y - movements[i - 1].y;

      if (Math.abs(dx1 - dx2) < 2 && Math.abs(dy1 - dy2) < 2) {
        straightLineCount++;
      }

      const angle = Math.abs(Math.atan2(dy2, dx2) - Math.atan2(dy1, dx1));
      if (Math.abs(angle - Math.PI / 2) < 0.2) {
        rightAngleCount++;
      }
    }

    if (straightLineCount / movements.length > this.config.mouse.straightLineThreshold) {
      checkup.straightLine = 1;
    }
    if (rightAngleCount > movements.length * this.config.mouse.rightAngleThreshold) {
      checkup.rightAngle = 1;
    }
    if (this.config.debug) {
      console.log('mouse', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  analyzeClicks() {
    if (this.metrics.clicks.length < this.config.clicks.minClicks) return 0;

    const checkup = {
      intervals: 0,
      naturalVariation: 0,
      samePosition: 0
    };

    const clicks = this.metrics.clicks;
    const intervals = [];
    let naturalVariation = 0;
    let samePositionCount = 0;

    for (let i = 1; i < clicks.length; i++) {
      const interval = clicks[i].time - clicks[i - 1].time;
      intervals.push(interval);

      if (i > 1) {
        const prevInterval = clicks[i - 1].time - clicks[i - 2].time;
        if (Math.abs(interval - prevInterval) > 150) {
          naturalVariation++;
        }
      }

      if (i > 0) {
        const dx = Math.abs(clicks[i].x - clicks[i - 1].x);
        const dy = Math.abs(clicks[i].y - clicks[i - 1].y);
        if (dx < this.config.clicks.positionTolerance && dy < this.config.clicks.positionTolerance) {
          samePositionCount++;
        }
      }
    }

    if (intervals.length > 0) {
      const {avgInterval, stdDev} = this.calculateStats(intervals);

      if (stdDev < this.config.clicks.stdDevThreshold && avgInterval < this.config.clicks.avgIntervalThreshold) {
        checkup.intervals = 1;
      }
    }

    if (naturalVariation / (clicks.length - 1) < this.config.clicks.naturalVariationThreshold) {
      checkup.naturalVariation = 1;
    }

    if (samePositionCount / clicks.length > this.config.clicks.samePositionThreshold) {
      checkup.samePosition = 1;
    }
    if (this.config.debug) {
      console.log('click', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  analyzeKeystrokes() {
    if (this.metrics.keystrokes.length < this.config.keystrokes.minKeystrokes) return 0;
    const checkup = {
      consistentTiming: 0,
      fastTyping: 0,
      highVariance: 0,
      naturalVariation: 0,
      repeatedPattern: 0,
      veryFastTyping: 0,
    };

    const keystrokes = this.metrics.keystrokes;
    const intervals = [];
    let naturalVariation = 0;
    let keySequence = [];

    for (let i = 1; i < keystrokes.length; i++) {
      const interval = keystrokes[i].time - keystrokes[i - 1].time;
      intervals.push(interval);
      keySequence.push(keystrokes[i - 1].key);

      if (i > 1) {
        const prevInterval = keystrokes[i - 1].time - keystrokes[i - 2].time;
        if (Math.abs(interval - prevInterval) > 75) {
          naturalVariation++;
        }
      }
    }

    if (intervals.length > 0) {
      const {avgInterval, stdDev} = this.calculateStats(intervals);

      if (stdDev < this.config.keystrokes.stdDevThreshold) {
        checkup.consistentTiming = 1;
        if (avgInterval < this.config.keystrokes.fastTypingThreshold) {
          checkup.fastTyping = 1;
        }
      }
      if (stdDev < this.config.keystrokes.highVarianceThreshold) {
        checkup.highVariance = 1;
      }
    }

    if (naturalVariation / (keystrokes.length - 1) < this.config.keystrokes.naturalVariationThreshold) {
      checkup.naturalVariation = 1;
    }

    const sequence = keySequence.join('');
    const repeatedPatterns = sequence.match(/(.+?)\1+/g) || [];
    if (repeatedPatterns.length > 2) {
      checkup.repeatedPattern = 1;
    }

    const veryFastTyping = intervals.filter(i => i < this.config.keystrokes.veryFastTypingThreshold).length;
    if (veryFastTyping / intervals.length > 0.7) {
      checkup.veryFastTyping = 1;
    }
    if (this.config.debug) {
      console.log('strokes', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  analyzeScrolls() {
    if (this.metrics.scrolls.length < this.config.scrolls.minScrolls) return 0;
    const checkup = {
      consistentScroll: 0,
      intervals: 0,
    };

    const scrolls = this.metrics.scrolls;
    const distances = [];
    const intervals = [];

    for (let i = 1; i < scrolls.length; i++) {
      const distance = Math.abs(scrolls[i].y - scrolls[i - 1].y);
      distances.push(distance);
      intervals.push(scrolls[i].time - scrolls[i - 1].time);
    }

    if (distances.length > 0) {
      const {avgInterval: avgDistance, stdDev: distanceStdDev} = this.calculateStats(distances);

      if (distanceStdDev < this.config.scrolls.stdDevThreshold && avgDistance > this.config.scrolls.avgDistanceThreshold) {
        checkup.consistentScroll = 1;
      }
    }

    // Анализ интервалов скролла
    if (intervals.length > 0) {
      const {stdDev} = this.calculateStats(intervals);
      if (stdDev < 10) checkup.intervals = 1;
    }
    if (this.config.debug) {
      console.log('scroll', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  analyzeInteractionTiming() {
    const checkup = {
      highInteractionRate: 0,
      lowInteractionRate: 0,
    };
    const interactions = this.metrics.interactions;
    const timeElapsed = this.config.behavior.minTimeThreshold / 1000;
    const interactionsPerSecond = interactions / timeElapsed;

    if (interactionsPerSecond > this.config.behavior.maxInteractionsPerSecondThreshold) {
      checkup.highInteractionRate = 1;
    }
    if (interactionsPerSecond < this.config.behavior.minInteractionsPerSecondThreshold) {
      checkup.lowInteractionRate = 1;
    }
    if (this.config.debug) {
      console.log('timing', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  analyzeBehaviorPatterns() {
    const checkup = {
      click: 0,
      mouse: 0,
      keystroke: 0,
    };

    const {mouseMovements, clicks, keystrokes} = this.metrics;

    // Анализ мышиных движений
    if (mouseMovements.length > 5) {
      const lastMovements = mouseMovements.slice(-5);
      const samePosition = lastMovements.every((m, i, arr) => i === 0 || (Math.abs(m.x - arr[i - 1].x) < 2 && Math.abs(m.y - arr[i - 1].y) < 2));
      if (samePosition) checkup.click = 1;
    }

    // Анализ кликов
    if (clicks.length > 3) {
      const intervals = clicks.slice(1).map((click, i) => click.time - clicks[i].time);
      const {variance} = this.calculateStats(intervals);
      if (variance < 100) checkup.mouse = 1;
    }

    // Анализ нажатий клавиш
    if (keystrokes.length > 10) {
      const intervals = keystrokes.slice(1).map((keystroke, i) => keystroke.time - keystrokes[i].time);
      const {variance} = this.calculateStats(intervals);
      if (variance < 50) checkup.keystroke = 1;
    }
    if (this.config.debug) {
      console.log('patterns', checkup);
    }
    return this.calculateItemScore(checkup);
  }

  // Вспомогательный метод для вычисления статистики
  calculateStats(values) {
    const avg = values.reduce((a, b) => a + b, 0) / values.length;
    const variance = values.reduce((sum, value) => sum + Math.pow(value - avg, 2), 0) / values.length;
    const stdDev = Math.sqrt(variance);

    return {avg, variance, stdDev};
  }

  calculateItemScore(checkup) {
    const values = Object.values(checkup);
    const onesCount = values.filter(value => value === 1).length;
    return (onesCount / values.length) * 100;
  }

  updateCookie(result) {
    this.hub.setComponentCookie('siubt', JSON.stringify(result));
  }
}
