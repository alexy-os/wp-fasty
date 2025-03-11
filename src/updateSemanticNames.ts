import * as fs from 'fs';
import * as path from 'path';

const filePath = path.join(__dirname, 'components', 'domAnalysis.json');

function updateSemanticNames() {
    // Чтение файла
    fs.readFile(filePath, 'utf8', (err, data) => {
        if (err) {
            console.error('Ошибка чтения файла:', err);
            return;
        }

        // Парсинг JSON
        const jsonData = JSON.parse(data);

        // Обновление семантических имен
        jsonData.forEach((item: any) => {
            if (item.semantic) {
                item.semantic = item.semantic.replace(/^s-index-/, '');
            }
        });

        // Запись обновленных данных обратно в файл
        fs.writeFile(filePath, JSON.stringify(jsonData, null, 2), (err) => {
            if (err) {
                console.error('Ошибка записи файла:', err);
            } else {
                console.log('Файл успешно обновлен!');
            }
        });
    });
}

// Вызов функции
updateSemanticNames(); 