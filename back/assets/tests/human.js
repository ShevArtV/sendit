// Тест, имитирующий поведение реального пользователя
async function simulateHumanBehavior() {
  console.log('🧑‍💻 Запуск теста: Имитация поведения человека...');

  // Функция для получения текущего счета
  const getScore = () => {
    const analysis = window.SendIt?.UserBehaviorTracker?.requestAnalysis();
    return analysis?.score || 0;
  };

  // Случайное число в диапазоне
  const randomInRange = (min, max) => Math.random() * (max - min) + min;

  // Случайная задержка с нормальным распределением
  const randomDelay = (base, variance) =>
    Math.max(10, base + (Math.random() * variance * 2 - variance));

  // 1. Естественное перемещение мыши
  const moveMouse = async (fromX, fromY, toX, toY, steps = 30) => {
    for (let i = 0; i <= steps; i++) {
      const progress = i / steps;
      // Кривая Безье для плавного ускорения/замедления
      const t = progress < 0.5
        ? 2 * progress * progress
        : -1 + (4 - 2 * progress) * progress;

      const x = fromX + (toX - fromX) * t;
      const y = fromY + (toY - fromY) * t;

      // Добавляем небольшую случайную дрожь
      const jitterX = Math.sin(Date.now() * 0.01) * 2;
      const jitterY = Math.cos(Date.now() * 0.007) * 2;

      document.dispatchEvent(new MouseEvent('mousemove', {
        clientX: x + jitterX,
        clientY: y + jitterY
      }));

      await new Promise(r => setTimeout(r, randomDelay(10, 3)));
    }
  };

  // 2. Естественные клики
  const naturalClick = async (x, y) => {
    // Небольшое смещение для каждого клика
    const offsetX = randomInRange(-3, 3);
    const offsetY = randomInRange(-3, 3);

    // Нажатие
    document.dispatchEvent(new MouseEvent('mousedown', {
      clientX: x + offsetX,
      clientY: y + offsetY,
      button: 0
    }));

    // Случайная задержка между нажатием и отпусканием
    await new Promise(r => setTimeout(r, randomDelay(100, 40)));

    // Отпускание
    document.dispatchEvent(new MouseEvent('mouseup', {
      clientX: x + offsetX,
      clientY: y + offsetY,
      button: 0
    }));

    // Клик
    document.dispatchEvent(new MouseEvent('click', {
      clientX: x + offsetX,
      clientY: y + offsetY,
      button: 0
    }));
  };

  // 3. Естественный ввод текста
  const typeText = async (text) => {
    for (let i = 0; i < text.length; i++) {
      const key = text.charAt(i);
      const keyCode = key.charCodeAt(0);
      const keyDown = new KeyboardEvent('keydown', {
        key,
        keyCode,
        which: keyCode,
        code: `Key${key.toUpperCase()}`
      });
      const keyPress = new KeyboardEvent('keypress', {
        key,
        keyCode,
        which: keyCode,
        charCode: keyCode
      });
      const keyUp = new KeyboardEvent('keyup', {
        key,
        keyCode,
        which: keyCode,
        code: `Key${key.toUpperCase()}`
      });

      document.dispatchEvent(keyDown);
      document.dispatchEvent(keyPress);
      document.dispatchEvent(keyUp);

      // Случайная задержка между нажатиями
      let delay = randomDelay(120, 80);
      // Иногда делаем более длинные паузы
      if (Math.random() < 0.1) delay += randomDelay(300, 200);
      // Пауза после пробела или точки
      if ([' ', '.', ','].includes(key)) delay += randomDelay(200, 100);

      await new Promise(r => setTimeout(r, delay));
    }
  };

  // 4. Естественное прокручивание
  const naturalScroll = async (pixels, direction = 'down') => {
    const steps = 20 + Math.floor(Math.random() * 10);
    const step = (direction === 'down' ? 1 : -1) * pixels / steps;

    for (let i = 0; i < steps; i++) {
      // Добавляем случайные колебания в прокрутку
      const jitter = Math.sin(Date.now() * 0.01) * 2;
      window.scrollBy(0, step + jitter);

      // Случайная задержка между шагами прокрутки
      await new Promise(r => setTimeout(r, randomDelay(30, 15)));

      // Иногда делаем микропаузы
      if (Math.random() < 0.2) {
        await new Promise(r => setTimeout(r, randomDelay(100, 50)));
      }
    }
  };

  // Выполняем последовательность действий, как настоящий пользователь
  try {
    // Начинаем с перемещения мыши из-за края экрана
    await moveMouse(-100, -100, 100, 100, 50);

    // Небольшая пауза, как будто пользователь оглядывается
    await new Promise(r => setTimeout(r, randomDelay(800, 300)));

    // Перемещаемся к кнопке
    await moveMouse(100, 100, 300, 300, 40);

    // Кликаем с естественной задержкой
    await naturalClick(300, 300);

    // Пауза перед набором текста
    await new Promise(r => setTimeout(r, randomDelay(500, 200)));

    // Набираем текст с естественной скоростью
    await typeText("Привет! Это тестовое сообщение от человека. ");
    await typeText("Я проверяю, насколько хорошо работает детектор ботов. ");

    // Прокручиваем страницу вниз
    await naturalScroll(500, 'down');

    // Делаем паузу, как будто читаем
    await new Promise(r => setTimeout(r, randomDelay(2000, 1000)));

    // Прокручиваем обратно вверх
    await naturalScroll(500, 'up');

    // Получаем итоговый счет
    const finalScore = await getScore();
    const analysis = window.SendIt?.UserBehaviorTracker?.requestAnalysis();

    // Выводим результат
    console.log('\n🎯 РЕЗУЛЬТАТ ТЕСТА');
    console.log('==================');
    console.log(`Итоговый счет: ${Math.round(finalScore)}/100`);

    if (finalScore < 30) {
      console.log('✅ Результат: ЧЕЛОВЕК');
      console.log('   Это поведение выглядит естественно и похоже на действия реального пользователя.');
    } else if (finalScore < 70) {
      console.log('⚠️  Результат: НЕОПРЕДЕЛЕНО');
      console.log('   Есть некоторые подозрительные моменты, но в целом похоже на человека.');
    } else {
      console.log('❌ Результат: БОТ');
      console.log('   Обнаружены признаки автоматизированного поведения.');
    }

    console.log('\n📊 Детали анализа:');
    console.table(analysis?.details || {});

  } catch (error) {
    console.error('Ошибка при выполнении теста:', error);
  }
}

// Запускаем тест
//simulateHumanBehavior().catch(console.error);
