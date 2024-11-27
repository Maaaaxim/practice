import asyncio
from pyppeteer import launch
import csv


async def get_data(start_page, end_page, filename):
    browser = await launch(headless=True)
    page = await browser.newPage()

    await page.goto(f'https://www.tablycjakalorijnosti.com.ua/tablytsya-yizhyi',
                    {'waitUntil': 'networkidle0'})

    table_data = []

    for i in range(start_page, end_page + 1):
        print(f'Iteration {i} из {end_page}...')

        dropdown_selector = '#select_98'
        await page.waitForSelector(dropdown_selector)
        await page.click(dropdown_selector)
        await asyncio.sleep(2)

        option_selector = '#select_option_103'
        await page.waitForSelector(option_selector)
        await page.click(option_selector)
        await asyncio.sleep(2)

        rows = await page.querySelectorAll('.md-table .md-body .md-row')
        for row in rows:
            cells = await row.querySelectorAll('td.md-cell')
            cell_data = await asyncio.gather(
                *[page.evaluate('(element) => element.textContent.trim()', cell) for cell in cells]
            )
            table_data.append(cell_data)

        next_page_selector = 'div[ng-click="paginationNext()"]'
        await page.waitForSelector(next_page_selector)
        await page.click(next_page_selector)
        await asyncio.sleep(5)

    await browser.close()

    with open(filename, 'w', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        writer.writerow(['Назва', 'Енергія', 'Білки', 'Вуглеводы', 'Жири', 'Волокна'])
        writer.writerows(table_data)

    print("Data saved'")


async def run_multiple_tasks():
    await asyncio.gather(
        get_data(1, 333, 'ukr_data.csv'),
    )


asyncio.get_event_loop().run_until_complete(run_multiple_tasks())
