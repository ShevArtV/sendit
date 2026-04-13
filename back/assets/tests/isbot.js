// Улучшенный тест для проверки детектора ботов
async function runEnhancedBotTest() {
  console.log('🚀 Запуск улучшенного теста детектора ботов...');

  // Функция для получения текущего счета
  const getScore = () => {
    const analysis = window.SendIt?.SessionLogger?.requestAnalysis();
    console.log('Текущий счет:', analysis?.score, 'Детали:', analysis?.details);
    return analysis?.score || 0;
  };

  // 1. Тест на повторяющиеся действия
  console.log('\n🔍 Тест 1: Повторяющиеся действия мыши');
  for (let i = 0; i < 10; i++) {
    const x = 100 + (i * 2);
    const y = 100 + (i * 2);
    const event = new MouseEvent('mousemove', { clientX: x, clientY: y });
    document.dispatchEvent(event);
    await new Promise(r => setTimeout(r, 16)); // 60 FPS
  }
  console.log('Счет после теста 1:', await getScore());

  // 2. Тест на идеальные клики
  console.log('\n🖱 Тест 2: Идеально ровные клики');
  for (let i = 0; i < 5; i++) {
    const click = new MouseEvent('click', {
      clientX: 300,
      clientY: 300,
      button: 0
    });
    document.dispatchEvent(click);
    await new Promise(r => setTimeout(r, 300)); // Ровно 300мс между кликами
  }
  console.log('Счет после теста 2:', await getScore());

  // 3. Тест на печать с постоянной скоростью
  console.log('\n⌨️ Тест 3: Печать с постоянной скоростью');
  const text = "This is a test message for bot detection.";
  for (let i = 0; i < text.length; i++) {
    const key = text.charAt(i);
    const keyCode = key.charCodeAt(0);
    const keyDown = new KeyboardEvent('keydown', { key, keyCode, which: keyCode });
    const keyUp = new KeyboardEvent('keyup', { key, keyCode, which: keyCode });
    document.dispatchEvent(keyDown);
    document.dispatchEvent(keyUp);
    await new Promise(r => setTimeout(r, 100)); // Ровно 100мс между нажатиями
  }
  console.log('Счет после теста 3:', await getScore());

  // 4. Тест на отсутствие естественных пауз
  console.log('\n⏱️ Тест 4: Отсутствие естественных пауз');
  const actions = 20;
  for (let i = 0; i < actions; i++) {
    const event = new MouseEvent('mousemove', {
      clientX: 100 + (i * 10),
      clientY: 100 + (i * 5)
    });
    document.dispatchEvent(event);
    await new Promise(r => setTimeout(r, 50)); // Слишком равномерные интервалы
  }
  console.log('Счет после теста 4:', await getScore());

  // 5. Тест на предсказуемую последовательность действий
  console.log('\n🔄 Тест 5: Предсказуемая последовательность');
  const sequence = ['mousemove', 'click', 'keydown', 'keyup'];
  for (let i = 0; i < 3; i++) {
    for (const action of sequence) {
      if (action === 'mousemove') {
        document.dispatchEvent(new MouseEvent('mousemove', { clientX: 200, clientY: 200 }));
      } else if (action === 'click') {
        document.dispatchEvent(new MouseEvent('click', { clientX: 200, clientY: 200 }));
      } else {
        const key = 'a';
        const keyCode = 65;
        document.dispatchEvent(new KeyboardEvent(action, { key, keyCode, which: keyCode }));
      }
      await new Promise(r => setTimeout(r, 100));
    }
  }
  console.log('Счет после теста 5:', await getScore());

  // Итоговый анализ
  console.log('\n📊 Итоговый счет:', await getScore());
  const finalScore = await getScore();

  if (finalScore > 50) {
    console.log('✅ Детектор успешно определил бота');
  } else if (finalScore > 30) {
    console.log('⚠️  Детектор заподозрил бота, но недостаточно уверен');
  } else {
    console.log('❌ Детектор не смог определить бота');
    console.log('Рекомендации:');
    console.log('1. Увеличьте штрафы за повторяющиеся действия');
    console.log('2. Добавьте анализ паттернов ввода');
    console.log('3. Учтите временные характеристики между действиями');
  }
}

// Запускаем улучшенный тест
//runEnhancedBotTest().catch(console.error);
