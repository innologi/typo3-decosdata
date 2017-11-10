plugin.tx_decosdata.settings {
	import.0 = 1
	level {
		1 = _LIST
		1 {
			paginate {
				pageLimit = 30
				perPageLimit = 50
			}
			itemType.0 = DOCUMENT
			contentField {
				1 {
					title.value = Naam Regelgeving
					content.0.field = TEXT9
					order {
						sort = ASC
						priority = 10
					}
				}
				2 {
					title.value = Datum Inwerkingtreding
					content.0 {
						field = DATE5
						queryOptions {
							0 {
								option = DateConversion
								args.format = %d-%m-%Y
							}
							1 {
								option = FilterItems
								args {
									filters {
										0 {
											value = NULL
											operator = IS NOT
										}
										1 {
											value = 
											operator = !=
										}
										2 {
											value = NOW()
											operator = <=
										}
									}
									matchAll = 1
								}
							}
						}
					}
					order {
						sort = DESC
						priority = 20
					}
				}
				3 {
					title.value = Datum Intrekking
					content.0 {
						field = DATE6
						queryOptions {
							0 {
								option = DateConversion
								args.format = %d-%m-%Y
							}
							1 {
								option = FilterItems
								args.filters {
									0 {
										value = NULL
										operator = IS
									}
									1 {
										value = 
										operator = =
									}
									2 {
										value = NOW()
										operator = >
									}
								}
							}
						}
					}
				}
				4 {
					title.value = Download
					content.0.blob = 1
					renderOptions {
						0.option = FileIcon
						1 {
							option = Wrapper
							args.wrap = || {render:FileSize}|
						}
						2.option = FileDownload
					}
				}
			}
		}
	}
}