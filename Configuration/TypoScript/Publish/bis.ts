plugin.tx_decosdata.settings {
	import {
		0 = 2
		1 = 3
	}
	breadcrumb {
		1.value = Vergaderingen
		2 {
			itemType.0 = FOLDER
			contentField.1.content.0.field = SUBJECT1
			queryOptions.0 {
				option = FilterItems
				args {
					filters {
						0 {
							parameter = _2
							operator = =
							field = SUBJECT1
						}
						1 {
							value = vergaderdossiers
							operator = =
							field = BOOKNAME
						}
						2 {
							value = 1
							operator = =
							field = BOL3
						}
					}
					matchAll = 1
				}
			}
		}
		3 {
			itemType.0 = FOLDER
			contentField.1.content.0 {
				field = DATE1
				queryOptions.0 {
					option = DateConversion
					args.format = %d-%m-%Y
				}
			}
			queryOptions {
				0 {
					option = RestrictById
					args.parameter = _3
				}
				1 {
					option = FilterItems
					args.filters.0 {
						value = 1
						operator = =
						field = BOL3
					}
				}
			}
		}
		4.value = Zaak
	}
	level {
		1 = _LIST
		1 {
			paginate {
				pageLimit = 20
				perPageLimit = 20
			}
			itemType.0 = FOLDER
			# @TODO ___temporary solution, until I know how I'm going to replace filterView and childView options from tx_decospublisher
			noItemId = 1
			contentField.1 {
				title.value = Vergaderingen
				content.0.field = SUBJECT1
				renderOptions.0 {
					option = LinkLevel
					args.level = 2
				}
				order {
					sort = ASC
					priority = 10
				}
			}
			queryOptions.0 {
				option = FilterItems
				args {
					filters {
						0 {
							value = vergaderdossiers
							operator = =
							field = BOOKNAME
						}
						1 {
							value = 1
							operator = =
							field = BOL3
						}
					}
					matchAll = 1
				}
			}
		}
		2 = _LIST
		2 {
			paginate {
				type = yearly
				pageLimit = 20
				field = DATE1
			}
			itemType.0 = FOLDER
			contentField.1 {
				title.value = Vergaderdatum
				content.0 {
					field = DATE1
					order {
						sort = DESC
						priority = 10
					}
					queryOptions.0 {
						option = DateConversion
						args.format = %d-%m-%Y
					}
				}
				renderOptions.0 {
					option = LinkLevel
					args {
						level = 3
						linkItem = 1
					}
				}
			}
			queryOptions.0 {
				option = FilterItems
				args {
					filters {
						# perfect replacement for filterView! using what is already there :D
						0 {
							parameter = _2
							operator = =
							field = SUBJECT1
						}
						1 {
							value = vergaderdossiers
							operator = =
							field = BOOKNAME
						}
						2 {
							value = 1
							operator = =
							field = BOL3
						}
					}
					matchAll = 1
				}
			}
		}
		# @TODO 1:4(1,1,1|1,1,2|1,1,3|1,*,*);
		3 = _COA
		#3.5 = USER_INT
		#3.5 {
		#	userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
		#	extensionName = StreamovationsVp
		#	pluginName = Video
		#	vendorName = Innologi
		#	# we can't override the configuration from here, because this action relies on FULL_TYPOSCRIPT which does not get merged
		#	switchableControllerActions.Video.1 = advancedShow
		#}
		3.10 = _SHOW
		3.10 {
			contentField {
				1 {
					title.value = Type vergadering:
					content.0.field = SUBJECT1
				}
				2 {
					title.value = Datum:
					content.0 {
						field = DATE1
						queryOptions.0 {
							option = DateConversion
							args.format = %d-%m-%Y
						}
					}
				}
			}
			queryOptions.0 {
				option = RestrictById
				args.parameter = _3
			}
		}
		3.20 = _LIST
		3.20 {
			paginate {
				pageLimit = 20
				perPageLimit = 10
			}
			itemType.0 = DOCUMENT
			contentField {
				1 {
					title.value = Agendanr.
					content.0.field = TEXT8
					order {
						sort = ASC
						priority = 10
						forceNumeric = 1
					}
				}
				2 {
					title.value = Document type
					content.0.field = SUBJECT1
					order {
						sort = ASC
						priority = 20
					}
				}
				3 {
					title.value = Inhoud document
					content.0.field = TEXT9
					order {
						sort = ASC
						priority = 30
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
				5 {
					title.value = Zaak
					queryOptions {
						0 {
							option = ParentInParent
							args.itemType.0 = FOLDER
						}
						1 {
							option = FilterRelations
							args {
								filters {
									0 {
										value = Zake%
										operator = LIKE
										field = BOOKNAME
									}
									1 {
										value = 1
										operator = =
										field = BOL3
									}
								}
								matchAll = 1
							}
						}
					}
					renderOptions {
						0 {
							option = CustomImage
							args {
								path = typo3conf/ext/decosdata/Resources/Public/CustomIcons/archive.gif
								# @TODO ____what if we do an option that specifically checks for relation, and if fails, stops options here? Or rather, if succeeds, introduces a batch of new options?
								requireRelation = 1
							}
						}
						1 {
							option = LinkLevel
							args {
								level = 4
								linkRelation = 1
							}
						}
					}
				}
				6 {
					title {
						value = Reg.datum
						renderOptions.0 {
							option = AddTagAttributes
							args {
								attributes.class = hide-me
								appendClass = 1
							}
							last = 1
						}
					}
					content.0 {
						field = DOCUMENT_DATE
						queryOptions.0 {
							option = DateConversion
							args.format = %d-%m-%Y
						}
					}
					renderOptions.0 {
						option = AddTagAttributes
						args {
							attributes.class = hide-me
							appendClass = 1
						}
						last = 1
					}
				}
				7 {
					title {
						value = Reg.nr.
						renderOptions.0 {
							option = AddTagAttributes
							args {
								attributes.class = hide-me
								appendClass = 1
							}
							last = 1
						}
					}
					content.0.field = MARK
					renderOptions.0 {
						option = AddTagAttributes
						args {
							attributes.class = hide-me
							appendClass = 1
						}
						last = 1
					}
				}
			}
			queryOptions {
				0 {
					option = RestrictByParentId
					args.parameter = _3
				}
				1 {
					option = FilterItems
					args.filters.0 {
						value = 1
						operator = =
						field = BOL3
					}
				}
			}
		}
		# @TODO ______hide 'hide-me' fields with JS + add switch
		4 = _LIST
		4 {
			paginate {
				pageLimit = 20
				perPageLimit = 20
			}
			itemType.0 = DOCUMENT
			contentField {
				1 {
					title.value = Lijstnr.
					content.0.field = NUM6
					order {
						sort = ASC
						priority = 10
					}
				}
				2 {
					title.value = Inhoud document
					content.0.field = TEXT9
				}
				3 {
					title.value = Download
					content.0.blob = 1
					renderOptions {
						0.option = FileIcon
						1 {
							option = Wrapper
							args.wrap = || {render:FileSize}|
						}
						# @FIX ______title fieldname? make sure to also check BIS lvl3 and Regelgeving
						2.option = FileDownload
					}
				}
				4 {
					title {
						value = Reg.datum
						renderOptions.0 {
							option = AddTagAttributes
							args {
								attributes.class = hide-me
								appendClass = 1
							}
							last = 1
						}
					}
					content.0 {
						field = DOCUMENT_DATE
						queryOptions.0 {
							option = DateConversion
							args.format = %d-%m-%Y
						}
						order {
							sort = DESC
							priority = 20
						}
					}
					renderOptions.0 {
						option = AddTagAttributes
						args {
							attributes.class = hide-me
							appendClass = 1
						}
						last = 1
					}
				}
				5 {
					title {
						value = Reg.nr.
						renderOptions.0 {
							option = AddTagAttributes
							args {
								attributes.class = hide-me
								appendClass = 1
							}
							last = 1
						}
					}
					content.0.field = MARK
					renderOptions.0 {
						option = AddTagAttributes
						args {
							attributes.class = hide-me
							appendClass = 1
						}
						last = 1
					}
				}
			}
			queryOptions {
				0 {
					option = RestrictByParentId
					args.parameter = _4
				}
				1 {
					option = FilterItems
					args.filters.0 {
						value = 1
						operator = =
						field = BOL3
					}
				}
			}
		}
	}
}